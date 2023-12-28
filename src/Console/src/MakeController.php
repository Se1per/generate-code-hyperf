<?php

namespace App\Lib\Console\src;

use Hyperf\Devtool\Generator\GeneratorCommand;

class MakeController extends GeneratorCommand
{
    public function __construct()
    {
        parent::__construct('gen:crud-controller');
    }

    public function configure()
    {
        $this->setDescription('Create a new controller class');

        parent::configure();
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/validation-request.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return $this->getConfig()['namespace'] ?? 'App\\Request';
    }
}