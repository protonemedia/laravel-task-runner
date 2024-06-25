<?php

return [
    // The default timeout for a task in seconds
    'default_timeout' => 60,

    // The view location for the tasks.
    'task_views' => 'tasks',

    // The connection to use for the tasks.
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

    // Default EOF value. Leave empty to generate a dynamic one.
    'eof' => '',

    // The temporary directory to store the private keys and scripts. Leave empty to use the system's temporary directory.
    'temporary_directory' => '',

    // Store the fakes in a file. This is useful for testing.
    'persistent_fake' => [
        'enabled' => env('TASK_RUNNER_PERSISTENT_FAKE', false),
        'storage_root' => storage_path('framework/testing/task-runner'),
    ],

    // The default timeout for uploading script file to server in seconds
    'upload_timeout' => 10,
];
