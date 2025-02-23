# Laravel Task Runner

A package to write Shell scripts like Blade Components and run them locally or on a remote server. Support for running tasks in the background and test assertions. Built upon the [Process feature](https://laravel.com/docs/10.x/processes) in Laravel.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/protonemedia/laravel-task-runner.svg?style=flat-square)](https://packagist.org/packages/protonemedia/laravel-task-runner)
[![run-tests](https://github.com/protonemedia/laravel-task-runner/actions/workflows/run-tests.yml/badge.svg)](https://github.com/protonemedia/laravel-task-runner/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/protonemedia/laravel-task-runner.svg?style=flat-square)](https://packagist.org/packages/protonemedia/laravel-task-runner)
[![Buy us a tree](https://img.shields.io/badge/Treeware-%F0%9F%8C%B3-lightgreen)](https://plant.treeware.earth/protonemedia/laravel-task-runner)

## Sponsor Us

[<img src="https://inertiaui.com/visit-card.jpg" />](https://inertiaui.com/inertia-table?utm_source=github&utm_campaign=laravel-task-runner)

❤️ We proudly support the community by developing Laravel packages and giving them away for free. If this package saves you time or if you're relying on it professionally, please consider [sponsoring the maintenance and development](https://github.com/sponsors/pascalbaljet) and check out our latest premium package: [Inertia Table](https://inertiaui.com/inertia-table?utm_source=github&utm_campaign=laravel-task-runner). Keeping track of issues and pull requests takes time, but we're happy to help!

## Installation

This package requires Laravel 11 and PHP 8.2 or higher. You can install the package via composer:

```bash
composer require protonemedia/laravel-task-runner
```

Optionally, you can publish the config file with:

```bash
php artisan vendor:publish --provider="ProtoneMedia\LaravelTaskRunner\ServiceProvider"
```

## Basic usage

You may use the Artisan `make:task` command to create a `Task` class:

```bash
php artisan make:task ComposerGlobalUpdate
```

This will generate two files: `app/Tasks/ComposerGlobalUpdate.php` and `resources/views/tasks/composer-global-update.blade.php`.

Once you've added your script to the Blade template, you may run it on your local machine by calling the `dispatch()` method:

```php
ComposerGlobalUpdate::dispatch();
```

Alternatively, if you don't want a separate Blade template, you may use the `--class` option (or `-c`):

```bash
php artisan make:task ComposerGlobalUpdate -c
```

This allows you to specify the script inline:

```php
class ComposerGlobalUpdate extends Task
{
    public function render(): string
    {
        return 'composer global update';
    }
}
```

## Task output

The `dispatch()` method returns an instance of `ProcessOutput`, which can return the output and exit code:

```php
$output = ComposerGlobalUpdate::dispatch();

$output->getBuffer();
$output->getExitCode();

$output->getLines();    // returns the buffer as an array
$output->isSuccessful();    // returns true when the exit code is 0
$output->isTimeout();    // returns true on a timeout
```

To interact with the underlying `ProcessResult`, you may call the `getIlluminateResult()` method:

```php
$output->getIlluminateResult();
```

## Script variables

Just like Blade Components, the public properties and methods of the Task class are available in the template:

```php
class GetFile extends Task
{
    public function __construct(public string $path)
    {
    }

    public function options()
    {
        return '-n';
    }
}
```

Blade template:

```blade
cat {{ $options() }} {{ $path }}
```

You can create a new instance of the Task using the static `make()` method:

```php
GetFile::make('/etc/hosts')->dispatch();
```

## Task options

You may specify a timeout. By default, the timeout is based on the `task-runner.default_timeout` config value.

```php
class ComposerGlobalUpdate extends Task
{
    protected int $timeout = 60;
}
```

## Run in background

You may run a task in the background:

```php
ComposerGlobalUpdate::inBackground()->dispatch();
```

It allows you to write the output to a file, as the `dispatch()` method won't return anything when the Task is still running in the background.

```php
ComposerGlobalUpdate::inBackground()
    ->writeOutputTo(storage_path('script.output'))
    ->dispatch();
```

## Run tasks on a remote server

In the `task-runner` configuration file, you may specify one or more remote servers:

```php
return [
    'connections' => [
        // 'production' => [
        //     'host' => '',
        //     'port' => '',
        //     'username' => '',
        //     'private_key' => '',
        //     'private_key_path' => '',
        //     'passphrase' => '',
        //     'script_path' => '',
        // ],
    ],
];
```

Now you may call the `onConnection()` method before calling other methods:

```php
ComposerGlobalUpdate::onConnection('production')->dispatch();

ComposerGlobalUpdate::onConnection('production')->inBackground()->dispatch();
```

## Task test assertions

You may call the `fake()` method to prevent tasks from running and make assertions after acting:

```php
use ProtoneMedia\LaravelTaskRunner\Facades\TaskRunner;

/** @test */
public function it_updates_composer_globally()
{
    TaskRunner::fake();

    $this->post('/api/composer/global-update');

    TaskRunner::assertDispatched(ComposerGlobalUpdate::class);
}
```

You may also use a callback to investigate the Task further:

```php
TaskRunner::assertDispatched(function (ComposerGlobalUpdate $task) {
    return $task->foo === 'bar';
});
```

If you type-hint the Task with `PendingTask`, you may verify the configuration:

```php
use ProtoneMedia\LaravelTaskRunner\PendingTask;

TaskRunner::assertDispatched(ComposerGlobalUpdate::class, function (PendingTask $task) {
    return $task->shouldRunInBackground();
});

TaskRunner::assertDispatched(ComposerGlobalUpdate::class, function (PendingTask $task) {
    return $task->shouldRunOnConnection('production');
});
```

To fake just some of the tasks, you may call the `fake()` method with a class or array of classes:

```php
TaskRunner::fake(ComposerGlobalUpdate::class);
TaskRunner::fake([ComposerGlobalUpdate::class]);
```

Alternatively, you may fake everything except a specific task:

```php
TaskRunner::fake()->dontFake(ComposerGlobalUpdate::class);
```

You may also supply a fake Task output:

```php
TaskRunner::fake([
    ComposerGlobalUpdate::class => 'Updating dependencies'
]);
```

Or use the `ProcessOutput` class to set the exit code as well:

```php
use ProtoneMedia\LaravelTaskRunner\ProcessOutput;

TaskRunner::fake([
    ComposerGlobalUpdate::class => ProcessOutput::make('Updating dependencies')->setExitCode(1);
]);
```

When you specify the Task output, you may also prevent unlisted Tasks from running:

```php
TaskRunner::preventStrayTasks();
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Other Laravel packages

* [`Inertia Table`](https://inertiaui.com/inertia-table?utm_source=github&utm_campaign=laravel-task-runner): The Ultimate Table for Inertia.js with built-in Query Builder.
* [`Laravel Blade On Demand`](https://github.com/protonemedia/laravel-blade-on-demand): Laravel package to compile Blade templates in memory.
* [`Laravel Cross Eloquent Search`](https://github.com/protonemedia/laravel-cross-eloquent-search): Laravel package to search through multiple Eloquent models.
* [`Laravel Eloquent Scope as Select`](https://github.com/protonemedia/laravel-eloquent-scope-as-select): Stop duplicating your Eloquent query scopes and constraints in PHP. This package lets you re-use your query scopes and constraints by adding them as a subquery.
* [`Laravel FFMpeg`](https://github.com/protonemedia/laravel-ffmpeg): This package provides an integration with FFmpeg for Laravel. The storage of the files is handled by Laravel's Filesystem.
* [`Laravel MinIO Testing Tools`](https://github.com/protonemedia/laravel-minio-testing-tools): Run your tests against a MinIO S3 server.
* [`Laravel Mixins`](https://github.com/protonemedia/laravel-mixins): A collection of Laravel goodies.
* [`Laravel Paddle`](https://github.com/protonemedia/laravel-paddle): Paddle.com API integration for Laravel with support for webhooks/events.
* [`Laravel Verify New Email`](https://github.com/protonemedia/laravel-verify-new-email): This package adds support for verifying new email addresses: when a user updates its email address, it won't replace the old one until the new one is verified.
* [`Laravel XSS Protection`](https://github.com/protonemedia/laravel-xss-protection): Laravel Middleware to protect your app against Cross-site scripting (XSS). It sanitizes request input, and it can sanatize Blade echo statements.

## Security

If you discover any security related issues, please email pascal@protone.media instead of using the issue tracker.

## Credits

* [Pascal Baljet](https://github.com/protonemedia)
* [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
