<?php

namespace ProtoneMedia\LaravelTaskRunner;

use ProtoneMedia\LaravelTaskRunner\Commands\TaskMakeCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ServiceProvider extends PackageServiceProvider
{
    public function registeringPackage()
    {
        $this->app->singleton(ProcessRunner::class, function () {
            return new ProcessRunner;
        });

        $this->app->singleton(TaskDispatcher::class, function ($app) {
            $taskDispatcher = new TaskDispatcher($app->make(ProcessRunner::class));
            $taskDispatcher->loadPersistentFake();

            return $taskDispatcher;
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-task-runner')
            ->hasConfigFile()
            ->hasCommand(TaskMakeCommand::class);
    }
}
