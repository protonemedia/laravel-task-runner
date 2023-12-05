<?php

namespace ProtoneMedia\LaravelTaskRunner;

use Illuminate\Contracts\Process\ProcessResult;

class ProcessOutput
{
    private string $buffer = '';

    private ?int $exitCode = null;

    private bool $timeout = false;

    private ?ProcessResult $illuminateResult = null;

    /**
     * @var callable|null
     */
    private $onOutput = null;

    /**
     * A PHP callback to run whenever there is some output available on STDOUT or STDERR.
     */
    public function onOutput(callable $callback): self
    {
        $this->onOutput = $callback;

        return $this;
    }

    /**
     * Appends the buffer to the output.
     *
     * @param  string  $buffer
     * @return void
     */
    public function __invoke(string $type, $buffer)
    {
        $this->buffer .= $buffer;

        if ($this->onOutput) {
            ($this->onOutput)($type, $buffer);
        }
    }

    /**
     * Helper to create a new instance.
     */
    public static function make(string $buffer = ''): static
    {
        $instance = new static;

        if ($buffer) {
            $instance('', $buffer);
        }

        return $instance;
    }

    public function getIlluminateResult(): ?ProcessResult
    {
        return $this->illuminateResult;
    }

    /**
     * Returns the buffer.
     */
    public function getBuffer(): string
    {
        return $this->buffer;
    }

    /**
     * Returns the buffer as an array of lines.
     */
    public function getLines(): array
    {
        return explode(PHP_EOL, $this->getBuffer());
    }

    /**
     * Returns the exit code.
     */
    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    /**
     * Checks if the process was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->getExitCode() === 0;
    }

    /**
     * Setter for the Illuminate ProcessResult instance.
     */
    public function setIlluminateResult(ProcessResult $result): self
    {
        $this->illuminateResult = $result;

        return $this;
    }

    /**
     * Setter for the exit code.
     */
    public function setExitCode(int $exitCode = null): self
    {
        $this->exitCode = $exitCode;

        return $this;
    }

    /**
     * Checks if the process timed out.
     */
    public function isTimeout(): bool
    {
        return $this->timeout;
    }

    /**
     * Setter for the timeout.
     */
    public function setTimeout(bool $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }
}
