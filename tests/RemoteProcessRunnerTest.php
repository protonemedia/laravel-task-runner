<?php

namespace ProtoneMedia\LaravelTaskRunner\Tests;

use Mockery;
use ProtoneMedia\LaravelTaskRunner\ProcessOutput;
use ProtoneMedia\LaravelTaskRunner\ProcessRunner;
use ProtoneMedia\LaravelTaskRunner\RemoteProcessRunner;

it('can determine a path on the remote server', function () {
    $processRunner = Mockery::mock(ProcessRunner::class);
    $connection = connection();

    $remoteProcessRunner = new RemoteProcessRunner($connection, $processRunner);
    expect($remoteProcessRunner->path('test'))->toBe('/root/.laravel-task-runner/test');
});

it('can verify whether the script path exists', function () {
    $connection = connection();
    $keyPath = $connection->getPrivateKeyPath();

    $processRunner = Mockery::mock(ProcessRunner::class);
    $processRunner->shouldReceive('run')
        ->once()
        ->withArgs(function ($pendingProcess) use ($keyPath) {
            expect($pendingProcess->command)->toBe("ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -i {$keyPath} -p 22 root@1.1.1.1 'bash -s' << 'LARAVEL-TASK-RUNNER'
mkdir -p /root/.laravel-task-runner
LARAVEL-TASK-RUNNER");

            return true;
        })
        ->andReturn((new ProcessOutput)->setExitCode(0));

    $remoteProcessRunner = new RemoteProcessRunner($connection, $processRunner);
    $remoteProcessRunner->verifyScriptDirectoryExists();
});

it('can upload a file to the server', function () {
    $connection = connection();
    $keyPath = $connection->getPrivateKeyPath();

    $processRunner = Mockery::mock(ProcessRunner::class);
    $processRunner->shouldReceive('run')
        ->once()
        ->withArgs(function ($pendingProcess) use ($keyPath) {
            expect($pendingProcess->command)
                ->toStartWith("scp -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -i {$keyPath} -P 22")
                ->toEndWith('/test.sh root@1.1.1.1:/root/.laravel-task-runner/test.sh');

            return true;
        })
        ->andReturn((new ProcessOutput)->setExitCode(0));

    $remoteProcessRunner = new RemoteProcessRunner($connection, $processRunner);
    $remoteProcessRunner->upload('test.sh', 'pwd');
});

it('can run an uploaded script on the server', function () {
    $connection = connection();
    $keyPath = $connection->getPrivateKeyPath();

    $processRunner = Mockery::mock(ProcessRunner::class);
    $processRunner->shouldReceive('run')
        ->once()
        ->withArgs(function ($pendingProcess) use ($keyPath) {
            expect($pendingProcess->command)->toBe("ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -i {$keyPath} -p 22 root@1.1.1.1 'bash -s' << 'LARAVEL-TASK-RUNNER'
bash /root/.laravel-task-runner/test.sh 2>&1 | tee /root/.laravel-task-runner/test.log
LARAVEL-TASK-RUNNER");

            return true;
        })
        ->andReturn((new ProcessOutput)->setExitCode(0));

    $remoteProcessRunner = new RemoteProcessRunner($connection, $processRunner);
    $remoteProcessRunner->runUploadedScript('test.sh', 'test.log', 60);
});

it('can run an uploaded script in the background without a timeout on the server', function () {
    $connection = connection();
    $keyPath = $connection->getPrivateKeyPath();

    $processRunner = Mockery::mock(ProcessRunner::class);
    $processRunner->shouldReceive('run')
        ->once()
        ->withArgs(function ($pendingProcess) use ($keyPath) {
            expect($pendingProcess->command)->toBe("ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -i {$keyPath} -p 22 root@1.1.1.1 'bash -s' << 'LARAVEL-TASK-RUNNER'
nohup bash /root/.laravel-task-runner/test.sh > /root/.laravel-task-runner/test.log 2>&1 &
LARAVEL-TASK-RUNNER");

            return true;
        })
        ->andReturn((new ProcessOutput)->setExitCode(0));

    $remoteProcessRunner = new RemoteProcessRunner($connection, $processRunner);
    $remoteProcessRunner->runUploadedScriptInBackground('test.sh', 'test.log', 0);
});

it('can run an uploaded script in the background with a timeout on the server', function () {
    $connection = connection();
    $keyPath = $connection->getPrivateKeyPath();

    $processRunner = Mockery::mock(ProcessRunner::class);
    $processRunner->shouldReceive('run')
        ->once()
        ->withArgs(function ($pendingProcess) use ($keyPath) {
            expect($pendingProcess->command)->toBe("ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -i {$keyPath} -p 22 root@1.1.1.1 'bash -s' << 'LARAVEL-TASK-RUNNER'
nohup timeout 60s bash /root/.laravel-task-runner/test.sh > /root/.laravel-task-runner/test.log 2>&1 &
LARAVEL-TASK-RUNNER");

            return true;
        })
        ->andReturn((new ProcessOutput)->setExitCode(0));

    $remoteProcessRunner = new RemoteProcessRunner($connection, $processRunner);
    $remoteProcessRunner->runUploadedScriptInBackground('test.sh', 'test.log', 60);
});
