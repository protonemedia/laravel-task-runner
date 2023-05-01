<?php

namespace ProtoneMedia\LaravelTaskRunner\Tests;

use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use ProtoneMedia\LaravelTaskRunner\Connection;
use ProtoneMedia\LaravelTaskRunner\Facades\TaskRunner;
use ProtoneMedia\LaravelTaskRunner\PendingTask;
use ProtoneMedia\LaravelTaskRunner\PersistentFakeTasks;
use ProtoneMedia\LaravelTaskRunner\ProcessOutput;
use ProtoneMedia\LaravelTaskRunner\ProcessRunner;
use ProtoneMedia\LaravelTaskRunner\TaskDispatcher;
use RuntimeException;

it('can fake all tasks', function () {
    TaskRunner::fake();

    DemoTask::dispatch();
    CustomTask::dispatch();
    CustomTask::dispatch();

    TaskRunner::assertDispatched(DemoTask::class);
    TaskRunner::assertDispatched(CustomTask::class);

    TaskRunner::assertDispatchedTimes(DemoTask::class, 1);
    TaskRunner::assertDispatchedTimes(CustomTask::class, 2);
});

it('can prevent stray tasks', function () {
    TaskRunner::fake(DemoTask::class);
    TaskRunner::preventStrayTasks();

    DemoTask::dispatch();

    try {
        CustomTask::dispatch();
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('Attempted dispatch task [ProtoneMedia\LaravelTaskRunner\Tests\CustomTask] without a matching fake.');

        return;
    }

    $this->fail('The expected exception was not thrown.');
});

it('can fake the output', function () {
    TaskRunner::fake([
        DemoTask::class => 'ok!',
        CustomTask::class => ProcessOutput::make('nope!')->setExitCode(127),
        Pwd::class,
    ]);

    expect(DemoTask::dispatch()->getBuffer())->toBe('ok!');
    expect(CustomTask::dispatch()->getBuffer())->toBe('nope!');
    expect(Pwd::dispatch()->getBuffer())->toBe('');

    TaskRunner::assertDispatched(DemoTask::class);
    TaskRunner::assertDispatched(CustomTask::class);
    TaskRunner::assertDispatched(Pwd::class);
});

it('can fake specific tests', function () {
    TaskRunner::fake(DemoTask::class);

    DemoTask::dispatch();
    Pwd::dispatch();

    TaskRunner::assertDispatched(DemoTask::class);
    TaskRunner::assertNotDispatched(Pwd::class);
});

it('can persist the fake settings', function () {
    config(['task-runner.persistent_fake.enabled' => true]);

    $directory = rtrim(config('task-runner.persistent_fake.storage_root'), '/');
    $storage = $directory.'/serialized';

    $test = new class
    {
        use PersistentFakeTasks;
    };

    $test->tearDownPersistentFakeTasks();
    expect($storage)->not->toBeFile();

    TaskRunner::fake(DemoTask::class)->dontFake(Pwd::class)->preventStrayTasks();
    DemoTask::dispatch();

    expect($storage)->toBeFile();

    $data = unserialize(file_get_contents($storage));

    expect($data)->toHaveKeys(['tasksToFake', 'tasksToDispatch', 'preventStrayTasks', 'dispatchedTasks']);
    expect($data['tasksToFake'])->toHaveCount(1);
    expect($data['tasksToDispatch'])->toHaveCount(1);
    expect($data['preventStrayTasks'])->toBeTrue();
    expect($data['dispatchedTasks'])->toHaveCount(1);

    $taskRunner = new TaskDispatcher(new ProcessRunner);
    $taskRunner->loadPersistentFake();

    $taskRunner->assertDispatched(DemoTask::class);
});

it('can allow specific tests', function () {
    TaskRunner::fake()->dontFake(Pwd::class);

    DemoTask::dispatch();
    Pwd::dispatch();

    TaskRunner::assertDispatched(DemoTask::class);
    TaskRunner::assertNotDispatched(Pwd::class);
});

it('can assert with a callback', function () {
    TaskRunner::fake();

    DemoTask::dispatch();
    CustomTask::inBackground()->dispatch();

    TaskRunner::assertDispatched(DemoTask::class);

    TaskRunner::assertDispatched(DemoTask::class, function (PendingTask $task) {
        return $task->shouldRunInForeground();
    });

    TaskRunner::assertDispatched(DemoTask::class, function (DemoTask $task) {
        return true;
    });

    TaskRunner::assertDispatched(CustomTask::class, function (PendingTask $task) {
        return $task->shouldRunInBackground();
    });

    //

    try {
        TaskRunner::assertDispatched(DemoTask::class, function (PendingTask $task) {
            return $task->shouldRunInBackground();
        });
    } catch (Exception $e) {
        expect($e)->toBeInstanceOf(ExpectationFailedException::class);
        expect($e->getMessage())->toContain('An expected task was not dispatched.');

        return;
    }

    $this->fail('The expected exception was not thrown.');
});

it('can the connection', function () {
    $connection = connection();

    config(['task-runner.connections.demo' => [
        'host' => '1.1.1.1',
        'port' => '22',
        'username' => 'root',
        'private_key' => 'secret',
        'passphrase' => 'password',
        'script_path' => '',
    ]]);

    TaskRunner::fake();

    DemoTask::onConnection($connection)->dispatch();

    TaskRunner::assertDispatched(DemoTask::class, function (PendingTask $task) {
        return $task->shouldRunOnConnection();
    });

    TaskRunner::assertDispatched(DemoTask::class, function (PendingTask $task) {
        return $task->shouldRunOnConnection(true);
    });

    TaskRunner::assertDispatched(DemoTask::class, function (PendingTask $task) {
        return $task->shouldRunOnConnection('demo');
    });

    TaskRunner::assertDispatched(DemoTask::class, function (PendingTask $task) use ($connection) {
        return $task->shouldRunOnConnection($connection);
    });

    TaskRunner::assertDispatched(DemoTask::class, function (PendingTask $task) {
        return $task->shouldRunOnConnection(function (Connection $c) {
            return $c->host === '1.1.1.1';
        });
    });
});
