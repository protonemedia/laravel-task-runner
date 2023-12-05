<?php

namespace ProtoneMedia\LaravelTaskRunner;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;

/*
 * @method static \ProtoneMedia\LaravelTaskRunner\PendingTask onConnection(string|Connection $connection)
 * @method static \ProtoneMedia\LaravelTaskRunner\PendingTask inBackground(bool $value = true)
 * @method static \ProtoneMedia\LaravelTaskRunner\PendingTask inForeground(bool $value = true)
 * @method static \ProtoneMedia\LaravelTaskRunner\PendingTask id(?string $id = null)
 * @method static \ProtoneMedia\LaravelTaskRunner\PendingTask writeOutputTo(?string $outputPath = null)
 * @method static \ProtoneMedia\LaravelTaskRunner\PendingTask as(string $id)
 * @method static \ProtoneMedia\LaravelTaskRunner\ProcessOutput dispatch(TaskDispatcher $taskDispatcher = null)
 */
abstract class Task
{
    use Macroable;

    /**
     * Returns the name of the task.
     */
    public function getName(): string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        return Str::headline(class_basename($this));
    }

    /**
     * Returns the timeout of the task in seconds.
     */
    public function getTimeout(): ?int
    {
        $timeout = property_exists($this, 'timeout')
            ? $this->timeout
            : config('task-runner.default_timeout', 60);

        return $timeout > 0 ? $timeout : null;
    }

    /**
     * Returns the view name of the task.
     */
    public function getView(): string
    {
        $view = property_exists($this, 'view')
            ? $this->view
            : Str::kebab(class_basename($this));

        $prefix = rtrim(config('task-runner.task_views', 'tasks'), '.');

        return $prefix.'.'.$view;
    }

    /**
     * Returns all public properties of the task.
     */
    private function getPublicProperties(): Collection
    {
        $properties = (new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC);

        return Collection::make($properties)->mapWithKeys(function (ReflectionProperty $property) {
            return [$property->getName() => $property->getValue($this)];
        });
    }

    /**
     * Returns all public methods of the task.
     */
    private function getPublicMethods(): Collection
    {
        $macros = Collection::make(static::$macros)
            ->mapWithKeys(function ($macro, $name) {
                return [$name => Closure::bind($macro, $this, get_class($this))];
            });

        $methods = (new ReflectionObject($this))->getMethods(ReflectionProperty::IS_PUBLIC);

        $methodCollection = Collection::make($methods)
            ->filter(function (ReflectionMethod $method) {
                if ($method->isConstructor() || $method->isDestructor() || $method->isStatic()) {
                    return false;
                }

                $ignoreMethods = [
                    'getData',
                    'getScript',
                    'getName',
                    'getTimeout',
                    'getView',
                ];

                if (in_array($method->getName(), $ignoreMethods)) {
                    return false;
                }

                return true;
            })
            ->mapWithKeys(function (ReflectionMethod $method) {
                return [$method->getName() => $method->getClosure($this)];
            });

        return $macros->merge($methodCollection);
    }

    /**
     * Returns all data that should be passed to the view.
     */
    public function getData(): array
    {
        return $this->getPublicProperties()
            ->merge($this->getPublicMethods())
            ->merge($this->getViewData())
            ->all();
    }

    /**
     * Returns the data that should be passed to the view.
     */
    public function getViewData(): array
    {
        return [];
    }

    /**
     * Returns the rendered script.
     */
    public function getScript(): string
    {
        if (method_exists($this, 'render')) {
            return Container::getInstance()->call([$this, 'render']);
        }

        return view($this->getView(), $this->getData())->render();
    }

    /**
     * Returns a new PendingTask with this task.
     */
    public function pending(): PendingTask
    {
        return new PendingTask($this);
    }

    /**
     * Returns a new PendingTask with this task.
     */
    public static function make(...$arguments): PendingTask
    {
        return (new static(...$arguments))->pending();
    }

    /**
     * Helper methods to create a new PendingTask.
     */
    public static function __callStatic($name, $arguments)
    {
        return (new static)->pending()->{$name}(...$arguments);
    }
}
