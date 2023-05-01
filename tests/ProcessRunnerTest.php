<?php

namespace ProtoneMedia\LaravelTaskRunner\Tests;

use Illuminate\Process\PendingProcess;
use Illuminate\Process\ProcessResult;
use Mockery;
use ProtoneMedia\LaravelTaskRunner\ProcessOutput;
use ProtoneMedia\LaravelTaskRunner\ProcessRunner;

it('can run a process and wait for it', function () {
    $processResult = Mockery::mock(ProcessResult::class);
    $processResult->shouldReceive('exitCode')->once()->withNoArgs()->andReturn(0);

    $pendingProcess = Mockery::mock(PendingProcess::class);
    $pendingProcess->shouldReceive('run')
        ->once()
        ->withArgs(function ($command, $output) {
            expect($output)->toBeInstanceOf(ProcessOutput::class);

            return true;
        })
        ->andReturn($processResult);

    $runner = new ProcessRunner;
    $output = $runner->run($pendingProcess);

    expect($output)->toBeInstanceOf(ProcessOutput::class);
    expect($output->getExitCode())->toBe(0);
    expect($output->isSuccessful())->toBeTrue();
});
