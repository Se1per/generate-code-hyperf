<?php

namespace Japool\Genconsole\Console;

use Japool\Genconsole\Console\src\AutoCodeHelp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;

use Hyperf\Devtool\Generator\GeneratorCommand;


#[Command]
class MakeManager extends GeneratorCommand
{
    #[Value('generator')]
    protected $config;

    use AutoCodeHelp;

    protected $sw = false;

    public function __construct()
    {
        parent::__construct('generate:crud-manager');
    }

    public function configure()
    {
        $this->setDescription('Create a new Manager class');
        parent::configure();
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/manager.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return $this->config['general']['manager'];
    }

    protected function qualifyClass(string $name): string
    {
        $name = $this->input->getArguments();

        $name = $name['name'].'Manager';

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
        $tableName['name'] = $this->unCamelCase($tableName['name']);
//        $dbPrefix = env('DB_PREFIX');
        $dbPrefix = \Hyperf\Support\env('DB_PREFIX');
        $result = $this->getTableColumnsComment($dbPrefix.$tableName['name']);

        $key = null;

        foreach ($result as $column) {
            if($column->Key == 'PRI' && !$key){
                $key = '\''.$column->Field.'\'';
            }
        }

        $stub = str_replace('{{ class }}', $this->camelCase($tableName['name']).'Manager', $stub);

        $stub = str_replace('{{ namespace }}', $this->config['general']['manager'], $stub);

        return $stub;
    }

}