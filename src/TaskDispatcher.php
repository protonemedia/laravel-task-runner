<?php

namespace ProtoneMedia\LaravelTaskRunner;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process as FacadesProcess;
use Illuminate\Support\Str;
use ReflectionFunction;
use RuntimeException;

class TaskDispatcher
{
    use MakesTestAssertions;
    use PersistsFakeTasks;

    private array|bool $tasksToFake = false;

    private array $tasksToDispatch = [];

    private bool $preventStrayTasks = false;

    private array $dispatchedTasks = [];

    public function __construct(private ProcessRunner $processRunner)
    {
    }

    /**
     * Runs the given task.
     */
    public function run(PendingTask $pendingTask): ?ProcessOutput
    {
        if ($fakeTask = $this->taskShouldBeFaked($pendingTask)) {
            $this->dispatchedTasks[] = $pendingTask;

            $this->storePersistentFake();

            return $fakeTask instanceof FakeTask
                ? $fakeTask->processOutput
                : new ProcessOutput;
        }

        if ($pendingTask->getConnection()) {
            return $this->runOnConnection($pendingTask);
        }

        if ($pendingTask->shouldRunInBackground()) {
            return $this->runInBackground($pendingTask);
        }

        $command = $pendingTask->storeInTemporaryDirectory();
        $timeout = $pendingTask->task->getTimeout();

        return $this->processRunner->run(
            FacadesProcess::command($command)->timeout($timeout)
        );
    }

    /**
     * Runs the given task in the background.
     *
     * @return ProcessOutput
     */
    private function runInBackground(PendingTask $pendingTask)
    {
        $command = Helper::scriptInBackground(
            scriptPath: $pendingTask->storeInTemporaryDirectory(),
            outputPath: $pendingTask->getOutputPath(),
            timeout: $pendingTask->task->getTimeout()
        );

        return $this->processRunner->run(
            FacadesProcess::command($command)->timeout(config('task-runner.connection_timeout', 10))
        );
    }

    /**
     * Runs the given task on the given connection.
     */
    private function runOnConnection(PendingTask $pendingTask): ProcessOutput
    {
        /** @var RemoteProcessRunner $runner */
        $runner = app()->makeWith(
            RemoteProcessRunner::class,
            ['connection' => $pendingTask->getConnection(), 'processRunner' => $this->processRunner, 'connectionTimeout' => config('task-runner.connection_timeout', 10)]
        );

        if ($outputCallbable = $pendingTask->getOnOutput()) {
            $runner->onOutput($outputCallbable);
        }

        $id = $pendingTask->getId() ?: Str::random(32);

        $runner->verifyScriptDirectoryExists()->upload("{$id}.sh", $pendingTask->task->getScript());

        return $pendingTask->shouldRunInBackground()
            ? $runner->runUploadedScriptInBackground("{$id}.sh", "{$id}.log", $pendingTask->task->getTimeout() ?: 0)
            : $runner->runUploadedScript("{$id}.sh", "{$id}.log", $pendingTask->task->getTimeout() ?: 0);
    }

    //

    /**
     * Fake all or some tasks.
     *
     * @param  array  $tasksToFake
     */
    public function fake(array|string $tasksToFake = []): self
    {
        $this->tasksToFake = Collection::wrap($tasksToFake)->map(function ($value, $key) {
            if (is_string($key) && is_string($value)) {
                return new FakeTask($key, ProcessOutput::make($value)->setExitCode(0));
            }

            if (is_string($value)) {
                return new FakeTask($value, ProcessOutput::make()->setExitCode(0));
            }

            if (is_string($key) && $value instanceof ProcessOutput) {
                return new FakeTask($key, $value);
            }
        })->filter()->values()->all();

        $this->storePersistentFake();

        return $this;
    }

    /**
     * Fake all or some tasks.
     *
     * @param  array  $tasksToFake
     */
    public function dontFake(array|string $taskToDispatch): self
    {
        $this->tasksToDispatch = array_merge($this->tasksToDispatch, Arr::wrap($taskToDispatch));

        $this->storePersistentFake();

        return $this;
    }

    /**
     * Prevents stray tasks from being executed.
     */
    public function preventStrayTasks(bool $prevent = true): self
    {
        $this->preventStrayTasks = $prevent;

        return $this;
    }

    /**
     * Returns a boolean if the task should be faked or the corresponding fake task.
     */
    private function taskShouldBeFaked(PendingTask $pendingTask): bool|FakeTask
    {
        foreach ($this->tasksToDispatch as $dontFake) {
            if ($pendingTask->task instanceof $dontFake) {
                return false;
            }
        }

        if ($this->tasksToFake === []) {
            return new FakeTask(get_class($pendingTask->task), ProcessOutput::make()->setExitCode(0));
        }

        if ($this->tasksToFake === false && ! config('task-runner.persistent_fake.enabled')) {
            return false;
        }

        $fakeTask = collect($this->tasksToFake ?: [])->first(function (FakeTask $fakeTask) use ($pendingTask) {
            return $pendingTask->task instanceof $fakeTask->taskClass;
        });

        if (! $fakeTask && $this->preventStrayTasks) {
            throw new RuntimeException('Attempted dispatch task ['.get_class($pendingTask->task).'] without a matching fake.');
        }

        return $fakeTask ?: false;
    }

    /**
     * Returns the dispatched tasks, filtered by a callback.
     */
    protected function faked(callable $callback): Collection
    {
        $this->loadPersistentFake();

        return collect($this->dispatchedTasks)
            ->filter(function (PendingTask $pendingTask) use ($callback) {
                $refFunction = new ReflectionFunction($callback);

                $parameters = $refFunction->getParameters();

                if ($typeHint = $parameters[0]->getType()->getName() ?? null) {
                    if (! $typeHint || $typeHint === PendingTask::class) {
                        return $callback($pendingTask);
                    }
                }

                return $callback($pendingTask->task);
            })
            ->values();
    }
}
