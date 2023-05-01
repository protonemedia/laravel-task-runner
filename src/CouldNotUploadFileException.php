<?php

namespace ProtoneMedia\LaravelTaskRunner;

use Exception;

class CouldNotUploadFileException extends Exception
{
    public readonly ProcessOutput $output;

    public static function fromProcessOutput(ProcessOutput $output): static
    {
        $exception = new static('Could not upload file');

        $exception->output = $output;

        return $exception;
    }
}
