<?php

namespace ProtoneMedia\LaravelTaskRunner;

use Illuminate\Support\Facades\Process as FacadesProcess;
use Illuminate\Support\Str;

class RemoteProcessRunner
{
    public function __construct(
        private Connection $connection,
        private ProcessRunner $processRunner
    ) {
    }

    /**
     * Runs the full path of given script on the remote server.
     */
    public function path(string $filename): string
    {
        return $this->connection->scriptPath.'/'.$filename;
    }

    /**
     * Creates the script directory on the remote server.
     *
     *
     * @throws \ProtoneMedia\LaravelTaskRunner\CouldNotCreateScriptDirectoryException
     */
    public function verifyScriptDirectoryExists(): self
    {
        $output = $this->run(
            script: 'mkdir -p '.$this->connection->scriptPath,
            timeout: 10
        );

        if ($output->isTimeout() || $output->getExitCode() !== 0) {
            throw CouldNotCreateScriptDirectoryException::fromProcessOutput($output);
        }

        return $this;
    }

    /**
     * Returns a set of common SSH options.
     */
    private function sshOptions(): array
    {
        $options = [
            '-o UserKnownHostsFile=/dev/null', // Don't use known hosts
            '-o StrictHostKeyChecking=no', // Disable host key checking
            "-i {$this->connection->getPrivateKeyPath()}",
        ];

        if ($this->connection->proxyJump) {
            $options[] = "-J {$this->connection->proxyJump}";
        }

        return $options;
    }

    /**
     * Formats the script and output paths, and runs the script.
     *
     * @return \ProtoneMedia\LaravelTaskRunner\ProcessOutput
     */
    public function runUploadedScript(string $script, string $output, int $timeout = 0): ProcessOutput
    {
        $scriptPath = $this->path($script);
        $outputPath = $this->path($output);

        return $this->run("bash {$scriptPath} 2>&1 | tee {$outputPath}", $timeout);
    }

    /**
     * Formats the script and output paths, and runs the script in the background.
     *
     * @return \ProtoneMedia\LaravelTaskRunner\ProcessOutput
     */
    public function runUploadedScriptInBackground(string $script, string $output, int $timeout = 0): ProcessOutput
    {
        $script = Helper::scriptInBackground(
            scriptPath: $this->path($script),
            outputPath: $this->path($output),
            timeout: $timeout,
        );

        return $this->run($script, 10);
    }

    /**
     * Wraps the script in a bash subshell command, and runs it over SSH.
     *
     * @return \ProtoneMedia\LaravelTaskRunner\ProcessOutput
     */
    private function run(string $script, int $timeout = 0): ProcessOutput
    {
        $command = implode(' ', [
            'ssh',
            ...$this->sshOptions(),
            "-p {$this->connection->port}",
            "{$this->connection->username}@{$this->connection->host}",
            Helper::scriptInSubshell($script),
        ]);

        $output = $this->processRunner->run(
            FacadesProcess::command($command)->timeout($timeout > 0 ? $timeout : null)
        );

        return $this->cleanupOutput($output);
    }

    /**
     * Removes the known hosts warning from the output.
     *
     * @param  \ProtoneMedia\LaravelTaskRunner\ProcessOutput  $processOutput
     * @return \ProtoneMedia\LaravelTaskRunner\ProcessOutput
     */
    private function cleanupOutput(ProcessOutput $processOutput): ProcessOutput
    {
        $buffer = $processOutput->getBuffer();

        if (Str::startsWith($buffer, 'Warning: Permanently added')) {
            $buffer = Str::after($buffer, "\n");
        }

        return ProcessOutput::make(trim($buffer))
            ->setExitCode($processOutput->getExitCode())
            ->setTimeout($processOutput->isTimeout());
    }

    /**
     * Uploads the given contents to the script directory with the given filename.
     *
     * @param  string  $filename
     * @param  string  $contents
     */
    public function upload($filename, $contents): self
    {
        $localPath = Helper::temporaryDirectoryPath($filename);
        file_put_contents($localPath, $contents);

        $command = implode(' ', [
            'scp',
            ...$this->sshOptions(),
            '-P '.$this->connection->port,
            $localPath,
            "{$this->connection->username}@{$this->connection->host}:".$this->path($filename),
        ]);

        $output = $this->processRunner->run(
            FacadesProcess::command($command)->timeout(10)
        );

        if ($output->isTimeout() || $output->getExitCode() !== 0) {
            throw CouldNotUploadFileException::fromProcessOutput($output);
        }

        return $this;
    }
}
