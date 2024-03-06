<?php

namespace ProtoneMedia\LaravelTaskRunner;

use Spatie\TemporaryDirectory\TemporaryDirectory;

class Helper
{
    /**
     * Wraps the given script in a subshell using bash's here document.
     *
     * @param  string  $output
     */
    public static function scriptInSubshell(string $script): string
    {
        $eof = static::eof($script);

        return implode(PHP_EOL, [
            "'bash -s' << '{$eof}'",
            $script,
            $eof,
        ]);
    }

    /**
     * Returns a temporary directory that will be deleted on shutdown.
     */
    public static function temporaryDirectory(): TemporaryDirectory
    {
        return tap(
            TemporaryDirectory::make(config('task-runner.temporary_directory') ?: ''),
            fn ($temporaryDirectory) => register_shutdown_function(fn () => $temporaryDirectory->delete())
        );
    }

    /**
     * Returns the path to the temporary directory.
     */
    public static function temporaryDirectoryPath(string $pathOrFilename = ''): string
    {
        return static::temporaryDirectory()->path($pathOrFilename);
    }

    /**
     * Use the nohup command to run a script in the background.
     */
    public static function scriptInBackground(string $scriptPath, ?string $outputPath = null, ?int $timeout = null): string
    {
        $outputPath = $outputPath ?: '/dev/null';

        $timeout = $timeout ?: 0;

        $nohup = $timeout > 0 ? "nohup timeout {$timeout}s" : 'nohup';

        return "{$nohup} bash {$scriptPath} > {$outputPath} 2>&1 &";
    }

    /**
     * Returns the EOF string.
     *
     * @param  string  $script
     * @return void
     */
    public static function eof($script = '')
    {
        if ($eof = config('task-runner.eof')) {
            return $eof;
        }

        return 'LARAVEL-TASK-RUNNER-'.md5($script);
    }
}
