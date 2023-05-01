<?php

namespace ProtoneMedia\LaravelTaskRunner;

use InvalidArgumentException;

class FakeTask
{
    public function __construct(
        public readonly string $taskClass,
        public readonly ProcessOutput $processOutput
    ) {
        if (trim($taskClass) === '') {
            throw new InvalidArgumentException('The task class cannot be empty.');
        }
    }
}
