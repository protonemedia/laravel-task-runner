# Laravel Task Runner Reference

Complete reference for `protonemedia/laravel-task-runner`.

Primary docs: https://github.com/protonemedia/laravel-task-runner#readme

## What this package does

- Lets you define “Tasks” that render to shell scripts.
- Scripts can be written in a Blade template file (default) or inline in the Task class.
- Tasks can run locally or via SSH-like remote connections.
- Supports running tasks in the background.
- Provides a fake/assertion API for tests.

Built upon Laravel’s Process feature.

## Installation

```bash
composer require protonemedia/laravel-task-runner
```

Optional config publish:

```bash
php artisan vendor:publish --provider="ProtoneMedia\LaravelTaskRunner\ServiceProvider"
```

## Creating tasks

### Generate a Task (Blade template + class)

```bash
php artisan make:task ComposerGlobalUpdate
```

This creates:

- `app/Tasks/ComposerGlobalUpdate.php`
- `resources/views/tasks/composer-global-update.blade.php`

Add your shell script content to the Blade template, then run:

```php
ComposerGlobalUpdate::dispatch();
```

### Generate a class-only task (inline render)

```bash
php artisan make:task ComposerGlobalUpdate -c
```

```php
use ProtoneMedia\LaravelTaskRunner\Task;

class ComposerGlobalUpdate extends Task
{
    public function render(): string
    {
        return 'composer global update';
    }
}
```

## Dispatching and reading output

`dispatch()` returns `ProcessOutput` (unless running in background).

```php
$output = ComposerGlobalUpdate::dispatch();

$output->getBuffer();
$output->getExitCode();

$output->getLines();        // buffer as array
$output->isSuccessful();    // exit code === 0
$output->isTimeout();       // timed out
```

Access the underlying Laravel `ProcessResult`:

```php
$result = $output->getIlluminateResult();
```

## Script variables (Task properties/methods)

Public properties and methods are available in the Blade template (similar to Blade components).

```php
class GetFile extends Task
{
    public function __construct(public string $path)
    {
    }

    public function options(): string
    {
        return '-n';
    }
}
```

Template:

```blade
cat {{ $options() }} {{ $path }}
```

Create a configured instance with `make()`:

```php
GetFile::make('/etc/hosts')->dispatch();
```

## Task options

### Timeout

A Task can specify its own timeout. Default comes from `task-runner.default_timeout`.

```php
class ComposerGlobalUpdate extends Task
{
    protected int $timeout = 60;
}
```

## Run in background

```php
ComposerGlobalUpdate::inBackground()->dispatch();
```

When running in background, `dispatch()` won’t return an output immediately.

Write output to a file:

```php
ComposerGlobalUpdate::inBackground()
    ->writeOutputTo(storage_path('script.output'))
    ->dispatch();
```

## Running tasks on a remote server

Configure connections in `config/task-runner.php`:

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

Then choose a connection:

```php
ComposerGlobalUpdate::onConnection('production')->dispatch();

ComposerGlobalUpdate::onConnection('production')
    ->inBackground()
    ->dispatch();
```

## Testing and assertions

Use the `TaskRunner` facade to fake tasks and assert dispatches.

```php
use ProtoneMedia\LaravelTaskRunner\Facades\TaskRunner;

TaskRunner::fake();

$this->post('/api/composer/global-update');

TaskRunner::assertDispatched(ComposerGlobalUpdate::class);
```

Assert with a callback to inspect the task instance:

```php
TaskRunner::assertDispatched(function (ComposerGlobalUpdate $task) {
    return $task->foo === 'bar';
});
```

Verify configuration via `PendingTask`:

```php
use ProtoneMedia\LaravelTaskRunner\PendingTask;

TaskRunner::assertDispatched(ComposerGlobalUpdate::class, function (PendingTask $task) {
    return $task->shouldRunInBackground();
});

TaskRunner::assertDispatched(ComposerGlobalUpdate::class, function (PendingTask $task) {
    return $task->shouldRunOnConnection('production');
});
```

### Partial fakes / exclusions

```php
TaskRunner::fake(ComposerGlobalUpdate::class);
TaskRunner::fake([ComposerGlobalUpdate::class]);

TaskRunner::fake()->dontFake(ComposerGlobalUpdate::class);
```

### Provide fake output

```php
use ProtoneMedia\LaravelTaskRunner\ProcessOutput;

TaskRunner::fake([
    ComposerGlobalUpdate::class => 'Updating dependencies',
]);

TaskRunner::fake([
    ComposerGlobalUpdate::class => ProcessOutput::make('Updating dependencies')->setExitCode(1),
]);
```

Prevent stray tasks:

```php
TaskRunner::preventStrayTasks();
```

## Common pitfalls / gotchas

- **Background tasks:** don’t expect a returned `ProcessOutput`; use `writeOutputTo()`.
- **Remote execution:** ensure keys/paths are configured; keep config key names stable.
- **Template quoting:** remember you are generating shell scripts; quoting/escaping can be subtle.
