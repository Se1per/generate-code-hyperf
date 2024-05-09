<?php

namespace Japool\Genconsole\Console;
use Hyperf\Devtool\Generator\GeneratorCommand;

class GenerateBase extends GeneratorCommand
{
    public function __construct()
    {
        parent::__construct('gen:generateBaseCommons');
    }

    public function configure()
    {
        $this->setDescription('Create a new BaseCommons class');
        parent::configure();
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/controller.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return $this->config['general']['controller'].'\\'.$this->config['general']['app'];
    }
}