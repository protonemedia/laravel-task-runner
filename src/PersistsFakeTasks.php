<?php

namespace ProtoneMedia\LaravelTaskRunner;

use Illuminate\Filesystem\Filesystem;

trait PersistsFakeTasks
{
    public function storePersistentFake()
    {
        if (! config('task-runner.persistent_fake.enabled')) {
            return;
        }

        $directory = rtrim(config('task-runner.persistent_fake.storage_root'), '/');
        $storage = $directory.'/serialized';

        (new Filesystem)->ensureDirectoryExists($directory);

        file_put_contents($storage, serialize([
            'tasksToFake' => $this->tasksToFake,
            'tasksToDispatch' => $this->tasksToDispatch,
            'preventStrayTasks' => $this->preventStrayTasks,
            'dispatchedTasks' => $this->dispatchedTasks,
        ]));
    }

    public function loadPersistentFake()
    {
        if (! config('task-runner.persistent_fake.enabled')) {
            return;
        }

        $directory = rtrim(config('task-runner.persistent_fake.storage_root'), '/');
        $storage = $directory.'/serialized';

        (new Filesystem)->ensureDirectoryExists($directory);

        $unserialized = file_exists($storage)
            ? rescue(fn () => unserialize(file_get_contents($storage)), [], false)
            : [];

        $this->tasksToFake = $unserialized['tasksToFake'] ?? false;
        $this->tasksToDispatch = $unserialized['tasksToDispatch'] ?? [];
        $this->preventStrayTasks = $unserialized['preventStrayTasks'] ?? false;
        $this->dispatchedTasks = $unserialized['dispatchedTasks'] ?? [];
    }
}
