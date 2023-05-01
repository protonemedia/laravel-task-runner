<?php

namespace ProtoneMedia\LaravelTaskRunner\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use ProtoneMedia\LaravelTaskRunner\Commands\TaskMakeCommand;

it('can generate a task class and view with a command', function () {
    (new Filesystem)->cleanDirectory(app_path('Tasks'));
    (new Filesystem)->cleanDirectory(resource_path('views/tasks'));

    Artisan::call(TaskMakeCommand::class, ['name' => 'TestTask']);

    expect($taskPath = app_path('Tasks/TestTask.php'))->toBeFile();
    expect(resource_path('views/tasks/test-task.blade.php'))->toBeFile();

    $task = file_get_contents($taskPath);

    expect($task)->toContain('class TestTask extends Task');
    expect($task)->not->toContain('public function render()');
});

it('can generate a task class without a view', function () {
    (new Filesystem)->cleanDirectory(app_path('Tasks'));
    (new Filesystem)->cleanDirectory(resource_path('views/tasks'));

    Artisan::call(TaskMakeCommand::class, ['name' => 'TestTask', '--class' => true]);

    expect($taskPath = app_path('Tasks/TestTask.php'))->toBeFile();
    expect(resource_path('views/tasks/test-task.blade.php'))->not->toBeFile();

    $task = file_get_contents($taskPath);

    expect($task)->toContain('public function render()');
});
