{{-- Laravel Task Runner Guidelines for AI Code Assistants --}}
{{-- Source: https://github.com/protonemedia/laravel-task-runner --}}
{{-- License: MIT | (c) ProtoneMedia --}}

## Laravel Task Runner

- `protonemedia/laravel-task-runner` lets you define shell-script Tasks (via Blade templates or inline rendering), dispatch them locally or over SSH, and run them in the background — built on Laravel's Process feature.
- Always activate the `laravel-task-runner-development` skill when working with Task classes, the `TaskRunner` facade, remote connections, background dispatching, or any code that extends `ProtoneMedia\LaravelTaskRunner\Task`.
