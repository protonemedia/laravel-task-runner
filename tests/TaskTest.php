<?php

namespace ProtoneMedia\LaravelTaskRunner\Tests;

use Illuminate\Support\Facades\View;
use Mockery;
use ProtoneMedia\LaravelTaskRunner\Connection;

it('can generate defaults based on the class name and configuration', function () {
    $task = new DemoTask;

    expect($task->getName())->toBe('Demo Task');
    expect($task->getTimeout())->toBe(60);
    expect($task->getView())->toBe('tasks.demo-task');
});

it('can return a custom name and timeout and view', function () {
    $task = new CustomTask;

    expect($task->getName())->toBe('Custom Name');
    expect($task->getTimeout())->toBe(120);
    expect($task->getView())->toBe('tasks.custom-view');
});

it('adds public properties to the data array', function () {
    $task = new CustomTask;

    expect($task->getData())->toHaveKey('someData');
});

it('adds view data to the data array', function () {
    $task = new CustomTask;

    expect($task->getData())->toHaveKey('hello');
});

it('add the public methods to the data array', function () {
    $task = new CustomTask;

    expect($task->getData())->toHaveKey('someMethod');
    expect($task->getData()['someMethod']())->toBe('bar');
});

it('add the macro methods to the data array', function () {
    $task = new CustomTask;

    CustomTask::macro('macroMethod', function () {
        return 'baz';
    });

    expect($task->getData())->toHaveKey('macroMethod');
    expect($task->getData()['macroMethod']())->toBe('baz');
});

it('renders the view with the data', function () {
    $task = new CustomTask;

    View::addLocation(__DIR__.'/views');

    expect($task->getScript())->toBe('baz foo bar');
});

it('can create a pending task with a static method', function () {
    $pendingTask = DemoTask::inBackground();
    expect($pendingTask->task)->toBeInstanceOf(DemoTask::class);
    expect($pendingTask->shouldRunInBackground())->toBeTrue();
    expect($pendingTask->getConnection())->toBeNull();

    $pendingTask = DemoTask::inForeground();
    expect($pendingTask->shouldRunInBackground())->toBeFalse();

    $pendingTask = DemoTask::onConnection(Mockery::mock(Connection::class));
    expect($pendingTask->getConnection())->toBeInstanceOf(Connection::class);
});
