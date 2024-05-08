<?php

namespace Japool\Genconsole\Console;

use Japool\Genconsole\Console\src\AutoCodeHelp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;
use Hyperf\Devtool\Generator\GeneratorCommand;

#[Command]
class MakeRepository extends GeneratorCommand
{
    #[value('repository')]
    protected $config;

    use AutoCodeHelp;

    public function __construct()
    {
        parent::__construct('gen:crud-repository');
    }

    public function configure()
    {
        $this->setDescription('Create a new repository class');
        parent::configure();
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/repository.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return $this->config['general']['repository'].'\\'.$this->config['general']['app'];
    }

    protected function qualifyClass(string $name): string
    {
        $name = $this->input->getArguments();

        $name = $name['name'].'Repository';

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

        $table = $this->unCamelCase($tableName['name']);
        $dbPrefix = env('DB_PREFIX');
        $result = $this->getTableColumnsComment($dbPrefix.$table);

        $key = null;

        foreach ($result as $column) {
            if($column->Key == 'PRI' && !$key){
                $key = '\''.$column->Field.'\'';
            }
        }

        $stub = str_replace('{{ primaryKey }}', $key, $stub);

        $stub = str_replace('{{ class }}', $this->camelCase($tableName['name']).'Repository', $stub);

        $stub = str_replace('{{ table }}', $this->camelCase($tableName['name']),$stub);

        $stub = str_replace('{{ namespace }}', $this->config['general']['repository'].'\\'.$this->config['general']['app'], $stub);

        return $stub;
    }
}