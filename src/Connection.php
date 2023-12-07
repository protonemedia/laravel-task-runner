<?php

namespace ProtoneMedia\LaravelTaskRunner;

use Illuminate\Support\Str;
use InvalidArgumentException;

class Connection
{
    private ?string $privateKeyPath = null;

    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $username,
        public readonly string $privateKey,
        public readonly string $scriptPath,
        public readonly ?string $proxyJump = null,
    ) {
        if (trim($host) === '') {
            throw new InvalidArgumentException('The host cannot be empty.');
        }

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('The port must be between 1 and 65535.');
        }

        if (trim($username) === '') {
            throw new InvalidArgumentException('The username cannot be empty.');
        }

        if (trim($privateKey) === '') {
            throw new InvalidArgumentException('The private key cannot be empty.');
        }

        if (trim($scriptPath) === '') {
            throw new InvalidArgumentException('The script path cannot be empty.');
        }
    }

    /**
     * Checks if the given connection is the same as this one.
     */
    public function is(Connection $connection): bool
    {
        return $this->host === $connection->host
            && $this->port === $connection->port
            && $this->username === $connection->username
            && $this->privateKey === $connection->privateKey
            && $this->scriptPath === $connection->scriptPath
            && $this->proxyJump === $connection->proxyJump;
    }

    /**
     * Creates a new connection from the given config connection name.
     */
    public static function fromConfig(string $connection): static
    {
        $config = config('task-runner.connections.'.$connection);

        if (! $config) {
            throw new ConnectionNotFoundException("Connection `{$connection}` not found.");
        }

        return static::fromArray($config);
    }

    /**
     * Creates a new connection from the given array.
     */
    public static function fromArray(array $config): static
    {
        $username = $config['username'] ?: '';

        $scriptPath = $config['script_path'] ?: null;

        if ($scriptPath) {
            $scriptPath = rtrim($scriptPath, '/');
        }

        if (! $scriptPath && $username) {
            $scriptPath = $config['username'] === 'root'
                ? '/root/.laravel-task-runner'
                : "/home/{$username}/.laravel-task-runner";
        }

        $privateKey = $config['private_key'] ?? null;

        if (is_callable($privateKey)) {
            $privateKey = $privateKey();
        }

        if (! $privateKey && array_key_exists('private_key_path', $config)) {
            $privateKey = file_get_contents($config['private_key_path']);
        }

        return new static(
            host: $config['host'] ?: null,
            port: $config['port'] ?: null,
            username: $username ?: null,
            privateKey: $privateKey,
            scriptPath: $scriptPath,
            proxyJump: $config['proxy_jump'] ?? null,
        );
    }

    /**
     * Returns the path to the private key.
     */
    public function getPrivateKeyPath(): string
    {
        if ($this->privateKeyPath) {
            return $this->privateKeyPath;
        }

        return tap(
            $this->privateKeyPath = Helper::temporaryDirectoryPath(Str::random(32).'.key'),
            function () {
                file_put_contents($this->privateKeyPath, $this->privateKey);

                // Make sure the private key is only readable by the current user
                chmod($this->privateKeyPath, 0600);
            }
        );
    }
}
