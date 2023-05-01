<?php

namespace ProtoneMedia\LaravelTaskRunner\Tests;

use Mockery;
use ProtoneMedia\LaravelTaskRunner\ProcessOutput;
use ProtoneMedia\LaravelTaskRunner\ProcessRunner;
use ProtoneMedia\LaravelTaskRunner\RemoteProcessRunner;
use ProtoneMedia\LaravelTaskRunner\Task;

class Pwd extends Task
{
    public function render()
    {
        return 'pwd';
    }
}

class PwdNoTimeout extends Task
{
    protected $timeout = 0;

    public function render()
    {
        return 'pwd';
    }
}

function addConnectionToConfig()
{
    config(['task-runner.connections.production' => [
        'host' => '1.1.1.1',
        'port' => '21',
        'username' => 'root',
        'private_key' => 'secret',
        'passphrase' => 'password',
        'script_path' => '',
    ]]);
}

it('can run a task on a local machine in the foreground', function () {
    $result = (new Pwd)->pending()->dispatch();

    expect($result->getBuffer())->toContain(realpath(__DIR__.'/..'));
});

it('can run a task on a local machine in the background', function () {
    $processRunner = Mockery::mock(ProcessRunner::class);

    $processRunner->shouldReceive('run')
        ->once()
        ->withArgs(function ($pendingProcess) {
            expect($pendingProcess->command)->toStartWith('nohup timeout 60s bash');
            expect($pendingProcess->command)->toEndWith('.sh > /dev/null 2>&1 &');

            return true;
        });

    app()->singleton(ProcessRunner::class, fn () => $processRunner);

    Pwd::inBackground()->dispatch();
});

it('can run a task on a local machine in the background without a timeout and specify an output path', function () {
    $processRunner = Mockery::mock(ProcessRunner::class);

    $processRunner->shouldReceive('run')
        ->once()
        ->withArgs(function ($pendingProcess) {
            expect($pendingProcess->command)->toStartWith('nohup bash');
            expect($pendingProcess->command)->toEndWith('.sh > /home/output.log 2>&1 &');

            return true;
        });

    app()->singleton(ProcessRunner::class, fn () => $processRunner);

    PwdNoTimeout::inBackground()->writeOutputTo('/home/output.log')->dispatch();
});

it('can run a task on a remote machine in the foreground', function () {
    addConnectionToConfig();

    $remoteProcessRunner = Mockery::mock(RemoteProcessRunner::class);

    $remoteProcessRunner->shouldReceive('verifyScriptDirectoryExists')
        ->once()
        ->withNoArgs()
        ->andReturnSelf();

    $remoteProcessRunner->shouldReceive('upload')
        ->once()
        ->withArgs(function ($filename, $contents) {
            expect($filename)->toEndWith('.sh');
            expect($contents)->toBe('pwd');

            return true;
        })
        ->andReturnSelf();

    $remoteProcessRunner->shouldReceive('runUploadedScript')
        ->once()
        ->withArgs(function ($scriptPath, $outputPath, $timeout) {
            expect($scriptPath)->toEndWith('.sh');
            expect($outputPath)->toEndWith('.log');
            expect($timeout)->toBe(60);

            return true;
        })
        ->andReturn($output = new ProcessOutput);

    app()->singleton(RemoteProcessRunner::class, fn () => $remoteProcessRunner);

    $result = Pwd::onConnection('production')->inForeground()->dispatch();

    expect($result)->toBe($output);
});

it('can run a task on a remote machine in the background', function () {
    addConnectionToConfig();

    $remoteProcessRunner = Mockery::mock(RemoteProcessRunner::class);

    $remoteProcessRunner->shouldReceive('verifyScriptDirectoryExists')
        ->once()
        ->withNoArgs()
        ->andReturnSelf();

    $remoteProcessRunner->shouldReceive('upload')
        ->once()
        ->withArgs(function ($filename, $contents) {
            expect($filename)->toEndWith('.sh');
            expect($contents)->toBe('pwd');

            return true;
        })
        ->andReturnSelf();

    $remoteProcessRunner->shouldReceive('runUploadedScriptInBackground')
        ->once()
        ->withArgs(function ($scriptPath, $outputPath, $timeout) {
            expect($scriptPath)->toEndWith('.sh');
            expect($outputPath)->toEndWith('.log');
            expect($timeout)->toBe(60);

            return true;
        })
        ->andReturn($output = new ProcessOutput);

    app()->singleton(RemoteProcessRunner::class, fn () => $remoteProcessRunner);

    $result = Pwd::onConnection('production')->inBackground()->dispatch();

    expect($result)->toBe($output);
});

it('can run a task on a remote machine in the background and define the script name', function () {
    addConnectionToConfig();

    $remoteProcessRunner = Mockery::mock(RemoteProcessRunner::class);

    $remoteProcessRunner->shouldReceive('verifyScriptDirectoryExists')
        ->once()
        ->withNoArgs()
        ->andReturnSelf();

    $remoteProcessRunner->shouldReceive('upload')
        ->once()
        ->withArgs(function ($filename, $contents) {
            expect($filename)->toBe('my-script.sh');
            expect($contents)->toBe('pwd');

            return true;
        })
        ->andReturnSelf();

    $remoteProcessRunner->shouldReceive('runUploadedScriptInBackground')
        ->once()
        ->withArgs(function ($scriptPath, $outputPath, $timeout) {
            expect($scriptPath)->toBe('my-script.sh');
            expect($outputPath)->toBe('my-script.log');
            expect($timeout)->toBe(60);

            return true;
        })
        ->andReturn($output = new ProcessOutput);

    app()->singleton(RemoteProcessRunner::class, fn () => $remoteProcessRunner);

    $result = Pwd::onConnection('production')->inBackground()->as('my-script')->dispatch();

    expect($result)->toBe($output);
});
