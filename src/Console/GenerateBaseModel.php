<?php

namespace Japool\Genconsole\Console;

use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;
use Hyperf\Devtool\Generator\GeneratorCommand;

#[Command]
class GenerateBaseModel extends GeneratorCommand
{
    #[value('generate')]
    protected $config;

    public function __construct()
    {
        parent::__construct('generate:generateBaseModel');
    }

    public function configure()
    {
        $this->setDescription('Create a new BaseModel class');
        parent::configure();
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/BaseModel.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return $this->config['general']['base'];
    }

    protected function qualifyClass(string $name): string
    {
        $name = $this->input->getArguments();

        $name = 'BaseModel';

        $namespace = $this->input->getOption('namespace');

        if (empty($namespace)) {
            $namespace = $this->getDefaultNamespace();
        }

        return $namespace . '\\' . $name;
    }

    /**
     * 设置类名和自定义替换内容
     * @param string $stub
     * @param string $name
     * @return string
     */
    protected function replaceClass(string $stub, $name): string
    {
        $stub = $this->replaceName($stub);
        return parent::replaceClass($stub, $name);
    }

    /**
     * 替换自定义内容
     * @param $stub
     * @return string|string[]
     */
    public function replaceName($stub)
    {
        $stub = str_replace('{{ namespace }}', $this->config['general']['base'], $stub);

        return $stub;
    }

}