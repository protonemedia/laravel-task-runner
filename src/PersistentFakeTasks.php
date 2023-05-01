<?php

namespace ProtoneMedia\LaravelTaskRunner;

use Illuminate\Filesystem\Filesystem;

trait PersistentFakeTasks
{
    public function setUpPersistentFakeTasks()
    {
        //
    }

    public function tearDownPersistentFakeTasks()
    {
        $directory = rtrim(config('task-runner.persistent_fake.storage_root'), '/');

        (new Filesystem)->cleanDirectory($directory);
    }
}
