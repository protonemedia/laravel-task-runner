<?php

namespace ProtoneMedia\LaravelTaskRunner;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

class PendingTask
{
    use Conditionable;
    use Macroable;

    private ?Connection $connection = null;

    private bool $inBackground = false;

    private ?string $id = null;

    private ?string $outputPath = null;

    /**
     * @var callable|null
     */
    private $onOutput = null;

    public function __construct(
        public readonly Task $task
    ) {
    }

    /**
     * Wraps the given task in a PendingTask instance.
     */
    public static function make(string|Task|PendingTask $task): static
    {
        if (is_string($task) && class_exists($task)) {
            $task = app($task);
        }

        return $task instanceof PendingTask ? $task : new PendingTask($task);
    }

    /**
     * A PHP callback to run whenever there is some output available on STDOUT or STDERR.
     */
    public function onOutput(callable $callback): self
    {
        $this->onOutput = $callback;

        return $this;
    }

    /**
     * Returns the callback that should be run whenever there is some output available on STDOUT or STDERR.
     */
    public function getOnOutput(): ?callable
    {
        return $this->onOutput;
    }

    /**
     * Returns the connection, if set.
     */
    public function getConnection(): ?Connection
    {
        return $this->connection;
    }

    /**
     * Setter for the connection.
     */
    public function onConnection(string|Connection $connection): self
    {
        $this->connection = is_string($connection)
            ? Connection::fromConfig($connection)
            : $connection;

        return $this;
    }

    /**
     * Checks if the task runs in the background.
     */
    public function shouldRunInBackground(): bool
    {
        return $this->inBackground;
    }

    /**
     * Checks if the task runs in the foreground.
     */
    public function shouldRunInForeground(): bool
    {
        return ! $this->inBackground;
    }

    /**
     * Sets the 'inBackground' property.
     */
    public function inBackground(bool $value = true): self
    {
        $this->inBackground = $value;

        return $this;
    }

    /**
     * Sets the 'inBackground' property to the opposite of the given value.
     */
    public function inForeground(bool $value = true): self
    {
        $this->inBackground = ! $value;

        return $this;
    }

    /**
     * Returns the 'id' property.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Sets the 'id' property.
     */
    public function id(string $id = null): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Alias for the 'id' method.
     */
    public function as(string $id): self
    {
        return $this->id($id);
    }

    /**
     * Returns the 'outputPath' property.
     */
    public function getOutputPath(): ?string
    {
        return $this->outputPath;
    }

    /**
     * Sets the 'outputPath' property.
     */
    public function writeOutputTo(string $outputPath = null): self
    {
        $this->outputPath = $outputPath;

        return $this;
    }

    /**
     * Checks if the given connection is the same as the connection of this task.
     */
    public function shouldRunOnConnection(bool|string|Connection|callable $connection = null): bool
    {
        if ($connection === null && $this->connection !== null) {
            return true;
        }

        if ($connection === true && $this->connection !== null) {
            return true;
        }

        if ($connection === false && $this->connection === null) {
            return true;
        }

        if (is_callable($connection)) {
            return $connection($this->connection) === true;
        }

        if (is_string($connection)) {
            $connection = Connection::fromConfig($connection);
        }

        if ($connection instanceof Connection) {
            return $connection->is($this->connection);
        }

        return false;
    }

    /**
     * Stores the script in a temporary directory and returns the path.
     */
    public function storeInTemporaryDirectory(): string
    {
        $id = $this->id ?: Str::random(32);

        return tap(Helper::temporaryDirectoryPath("{$id}.sh"), function ($path) {
            file_put_contents($path, $this->task->getScript());
            chmod($path, 0700);
        });
    }

    /**
     * Dispatches the task to the given task runner.
     */
    public function dispatch(TaskDispatcher $taskDispatcher = null): ?ProcessOutput
    {
        /** @var TaskDispatcher */
        $taskDispatcher = $taskDispatcher ?: app(TaskDispatcher::class);

        return $taskDispatcher->run($this);
    }
}
