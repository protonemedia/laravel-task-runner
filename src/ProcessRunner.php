<?php

namespace ProtoneMedia\LaravelTaskRunner;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Process\PendingProcess;

class ProcessRunner
{
    /**
     * Runs the given process and waits for it to finish.
     *
     * @return \ProtoneMedia\LaravelTaskRunner\ProcessOutput
     */
    public function run(PendingProcess $process): ProcessOutput
    {
        return tap(new ProcessOutput, function (ProcessOutput $output) use ($process) {
            $timeout = false;

            try {
                $illuminateResult = $process->run(output: $output);
            } catch (ProcessTimedOutException $e) {
                $illuminateResult = $e->result;
                $timeout = true;
            }

            $output->setIlluminateResult($illuminateResult);
            $output->setExitCode($illuminateResult->exitCode());
            $output->setTimeout($timeout);
        });
    }
}
