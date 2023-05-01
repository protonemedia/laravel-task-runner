<?php

namespace ProtoneMedia\LaravelTaskRunner\Facades;

use Illuminate\Support\Facades\Facade;
use ProtoneMedia\LaravelTaskRunner\PendingTask;
use ProtoneMedia\LaravelTaskRunner\ProcessOutput;
use ProtoneMedia\LaravelTaskRunner\TaskDispatcher;

/**
 * @method static self assertDispatched(string|callable $taskClass, callable $additionalCallback = null)
 * @method static self assertDispatchedTimes(string|callable $taskClass, int $times = 1, callable $additionalCallback = null)
 * @method static self assertNotDispatched(string|callable $taskClass, callable $additionalCallback = null)
 * @method static self fake(array|string $tasksToFake = [])
 * @method static self dontFake(array|string $taskToDispatch)
 * @method static self preventStrayTasks(bool $prevent = true)
 * @method static ProcessOutput|null run(PendingTask $pendingTask)
 *
 * @see \ProtoneMedia\LaravelTaskRunner\TaskDispatcher
 */
class TaskRunner extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TaskDispatcher::class;
    }
}
