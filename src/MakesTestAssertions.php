<?php

namespace ProtoneMedia\LaravelTaskRunner;

use PHPUnit\Framework\Assert as PHPUnit;
use ReflectionFunction;

trait MakesTestAssertions
{
    public function assertDispatched(string|callable $taskClass, callable $additionalCallback = null): self
    {
        $faked = $this->faked($this->makeAssertCallback($taskClass, $additionalCallback));

        PHPUnit::assertTrue(
            $faked->count() > 0,
            'An expected task was not dispatched.'
        );

        return $this;
    }

    public function assertNotDispatched(string|callable $taskClass, callable $additionalCallback = null): self
    {
        $faked = $this->faked($this->makeAssertCallback($taskClass, $additionalCallback));

        PHPUnit::assertTrue(
            $faked->count() === 0,
            'An unexpected task was dispatched.'
        );

        return $this;
    }

    public function assertDispatchedTimes(string|callable $taskClass, int $times = 1, callable $additionalCallback = null): self
    {
        $count = $this->faked($this->makeAssertCallback($taskClass, $additionalCallback))->count();

        PHPUnit::assertSame(
            $times,
            $count,
            "The expected [{$taskClass}] task was dispatched {$count} times instead of {$times} times."
        );

        return $this;
    }

    protected function typeNameOfFirstParameter(callable $callback = null): ?string
    {
        if (! $callback) {
            return null;
        }

        $reflection = new ReflectionFunction($callback);

        $parameters = $reflection->getParameters();

        if (empty($parameters)) {
            return null;
        }

        return $parameters[0]?->getType()?->getName() ?: null;
    }

    protected function callbackExpectsPendingTask(callable $callback = null): bool
    {
        $typeName = $this->typeNameOfFirstParameter($callback);

        return ! $typeName || $typeName === PendingTask::class;
    }

    protected function makeAssertCallback(string|callable $taskClass, callable $additionalCallback = null)
    {
        if (! $additionalCallback) {
            $additionalCallback = fn () => true;
        }

        if (is_string($taskClass)) {
            return function (PendingTask $pendingTask) use ($taskClass, $additionalCallback) {
                return $pendingTask->task instanceof $taskClass
                    && $additionalCallback($this->callbackExpectsPendingTask($additionalCallback) ? $pendingTask : $pendingTask->task);
            };
        }

        return function (PendingTask $pendingTask) use ($taskClass, $additionalCallback) {
            $class = $this->typeNameOfFirstParameter($taskClass);

            return ($class === PendingTask::class || $pendingTask->task instanceof $class)
                && $taskClass($this->callbackExpectsPendingTask($taskClass) ? $pendingTask : $pendingTask->task)
                && $additionalCallback($this->callbackExpectsPendingTask($additionalCallback) ? $pendingTask : $pendingTask->task);
        };
    }
}
