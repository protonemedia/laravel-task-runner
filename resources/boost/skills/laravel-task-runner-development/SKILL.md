---
name: laravel-task-runner-development
description: Build and work with protonemedia/laravel-task-runner features including defining Task classes, dispatching scripts locally or via SSH, running tasks in the background, and testing with the TaskRunner facade.
license: MIT
metadata:
  author: ProtoneMedia
---

# Laravel Task Runner Development

## Overview
Use protonemedia/laravel-task-runner to define shell-script Tasks rendered via Blade templates or inline, dispatch them locally or over SSH, run them in the background, and assert dispatches in tests. Built on Laravel’s Process feature.

## When to Activate
- Activate when working with shell-script Tasks, background dispatching, or remote SSH execution in Laravel.
- Activate when code references `Task`, `TaskRunner`, `PendingTask`, `ProcessOutput`, or the `task-runner` config.
- Activate when the user wants to create, dispatch, or test Tasks attached to shell scripts.

## Scope
- In scope: creating Task classes, dispatching tasks, reading output, background execution, remote connections, testing with `TaskRunner::fake()`, configuration.
- Out of scope: modifying this package’s internal source code unless the user explicitly says they are contributing to the package.

## Workflow
1. Identify the task (creating a Task class, dispatching, remote execution, testing, etc.).
2. Read `references/laravel-task-runner-guide.md` and focus on the relevant section.
3. Apply the patterns from the reference, keeping code minimal and Laravel-native.

## Core Concepts

### Creating a Task
Every Task extends `ProtoneMedia\LaravelTaskRunner\Task` and renders to a shell script:

```php
use ProtoneMedia\LaravelTaskRunner\Task;

class ComposerGlobalUpdate extends Task
{
    public function render(): string
    {
        return ‘composer global update’;
    }
}
```

### Dispatching and Output
```php
$output = ComposerGlobalUpdate::dispatch();

$output->isSuccessful();    // exit code === 0
$output->getBuffer();       // raw output string
$output->getLines();        // output as array
```

### Background Execution
```php
ComposerGlobalUpdate::inBackground()
    ->writeOutputTo(storage_path(‘script.output’))
    ->dispatch();
```

### Remote Connections
```php
ComposerGlobalUpdate::onConnection(‘production’)->dispatch();
```

### Testing
```php
use ProtoneMedia\LaravelTaskRunner\Facades\TaskRunner;

TaskRunner::fake();
// ... trigger code ...
TaskRunner::assertDispatched(ComposerGlobalUpdate::class);
```

## Do and Don’t

Do:
- Always extend `ProtoneMedia\LaravelTaskRunner\Task` for every task class.
- Use `TaskRunner::fake()` and `assertDispatched()` to test task dispatches.
- Use `writeOutputTo()` when running tasks in the background to capture output.
- Use `make()` to pass constructor arguments when dispatching a parameterised Task.

Don’t:
- Don’t expect a returned `ProcessOutput` from background tasks — use `writeOutputTo()`.
- Don’t forget to configure SSH keys and paths before dispatching on a remote connection.
- Don’t invent undocumented methods or options; stick to the reference guide.

## References
- `references/laravel-task-runner-guide.md`
