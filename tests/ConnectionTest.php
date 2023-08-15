<?php

use ProtoneMedia\LaravelTaskRunner\Connection;

function addConnectionToConfig()
{
    config(['task-runner.connections.production' => [
        'host' => '1.1.1.1',
        'port' => '21',
        'username' => 'root',
        'private_key' => fn() => 'secret',
        'passphrase' => 'password',
        'script_path' => '',
    ]]);
}

it('can resolve a private key from a callable', function () {
    addConnectionToConfig();

    $connection = Connection::fromConfig('production');

    expect($connection->privateKey)->toBe('secret');
});
