<?php

namespace Japool\Genconsole\Console;

use Japool\Genconsole\Console\src\AutoCodeHelp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;
use Hyperf\Devtool\Generator\GeneratorCommand;


#[Command]
class MakeService extends GeneratorCommand
{
    #[Value('generator')]
    protected $config;

    use AutoCodeHelp;

    public function __construct()
    {
        parent::__construct('generate:crud-service');
    }

    public function configure()
    {
        $this->setDescription('Create a new service class');
        parent::configure();
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/service.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return $this->config['general']['service'];
    }

    protected function qualifyClass(string $name): string
    {
        $name = $this->input->getArguments();

        $name = $name['name'].'Service';

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
        $stub = $this->replaceName($stub); //替换自定义内容
        return parent::replaceClass($stub, $name);
    }

    public function replaceName($stub)
    {
        $tableName = $this->input->getArguments();

        $stub = str_replace('{{ class }}', $this->camelCase($tableName['name']).'Service', $stub);

        $stub = str_replace('{{ smallTable }}', $tableName['name'],$stub);

        $stub = str_replace('{{ table }}', $this->camelCase($tableName['name']),$stub);

        $stub = str_replace('{{ namespace }}', $this->config['general']['service'], $stub);

        $stub = str_replace('{{ repository }}',$this->config['general']['repository'],$stub);
        
        $stub = str_replace('{{ manager }}',$this->config['general']['manager'],$stub);

//        $stub = str_replace('{{ app }}',$this->config['general']['app'] , $stub);

        $stub = str_replace('{{ base }}', $this->config['general']['base'],$stub);
        return $stub;
    }
}