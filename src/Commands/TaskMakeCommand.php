<?php

namespace ProtoneMedia\LaravelTaskRunner\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:task')]
class TaskMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:task';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Task';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'make:task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Task class';

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        if (parent::handle() === false) {
            return false;
        }

        if ($this->option('class')) {
            return;
        }

        (new Filesystem)->ensureDirectoryExists(
            $path = resource_path('views/'.config('task-runner.task_views', 'tasks'))
        );

        touch($path.'/'.$this->viewName().'.blade.php');
    }

    /**
     * Generate the name of the view.
     */
    protected function viewName(): string
    {
        return Str::kebab($this->getNameInput());
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->option('class')
            ? $this->resolveStubPath('/stubs/task.stub')
            : $this->resolveStubPath('/stubs/task-view.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
                        ? $customPath
                        : __DIR__.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Tasks';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the class already exists'],
            ['class', 'c', InputOption::VALUE_NONE, 'Create only the Task class, not the Blade template'],
        ];
    }
}
