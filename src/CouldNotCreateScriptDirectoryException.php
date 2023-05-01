<?php

namespace ProtoneMedia\LaravelTaskRunner;

use Exception;

class CouldNotCreateScriptDirectoryException extends Exception
{
    public readonly ProcessOutput $output;

    public static function fromProcessOutput(ProcessOutput $output): static
    {
        $exception = new static('Could not create script directory');

        $exception->output = $output;

        return $exception;
    }
}
