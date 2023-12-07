<?php

use ProtoneMedia\LaravelTaskRunner\Connection;

function addConnectionToConfig($callable = true, $path = false)
{
    config(['task-runner.connections.production' => [
        'host' => '1.1.1.1',
        'port' => '21',
        'username' => 'root',
        'private_key' => $callable ? fn () => 'secret' : null,
        'private_key_path' => $path ? __DIR__.'/private_key' : null,
        'passphrase' => 'password',
        'script_path' => '',
    ]]);
}

it('can resolve a private key from a callable', function () {
    addConnectionToConfig();

    $connection = Connection::fromConfig('production');

    expect($connection->privateKey)->toBe('secret');
});

it('can resolve a private key from a path', function () {
    addConnectionToConfig(callable: false, path: true);

    $connection = Connection::fromConfig('production');

    expect($connection->privateKey)->toBe('secret2');
});
