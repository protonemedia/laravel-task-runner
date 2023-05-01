<?php

use ProtoneMedia\LaravelTaskRunner\Connection;
use ProtoneMedia\LaravelTaskRunner\Tests\TestCase;

function connection()
{
    return Connection::fromArray([
        'host' => '1.1.1.1',
        'port' => '22',
        'username' => 'root',
        'private_key' => 'secret',
        'passphrase' => 'password',
        'script_path' => '',
    ]);
}

uses(TestCase::class)->in(__DIR__);
