<?php

namespace ProtoneMedia\LaravelTaskRunner\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use ProtoneMedia\LaravelTaskRunner\ServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['task-runner.eof' => 'LARAVEL-TASK-RUNNER']);
    }

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }
}
