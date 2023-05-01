<?php

namespace ProtoneMedia\LaravelTaskRunner\Tests;

use ProtoneMedia\LaravelTaskRunner\Task;

class CustomTask extends Task
{
    protected $name = 'Custom Name';

    protected $timeout = 120;

    protected $view = 'custom-view';

    public $someData = 'foo';

    public function someMethod()
    {
        return 'bar';
    }

    public function getViewData(): array
    {
        return ['hello' => 'world'];
    }
}
