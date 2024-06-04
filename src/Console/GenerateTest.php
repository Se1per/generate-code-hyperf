<?php

namespace Japool\Genconsole\Console;

use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;
use Hyperf\Devtool\Generator\GeneratorCommand;
use Japool\Genconsole\Console\src\AutoCodeHelp;

#[Command]
class GenerateTest extends GeneratorCommand
{
    #[value('generate')]
    protected $config;
    
    use AutoCodeHelp;
    
    public function __construct()
    {
        parent::__construct('generate:generateTest');
    }

    public function configure()
    {
        $this->setDescription('Create a new Test class');
        parent::configure();
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/Test.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\Test';
    }

    protected function qualifyClass(string $name): string
    {
        $name = $this->input->getArguments();

        $name = $name['name'].'ControllerTest';

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
        //BaseRepository
    }

    /**
     * 替换自定义内容
     * @param $stub
     * @return string|string[]
     */
    public function replaceName($stub)
    {
        $stub = str_replace('{{ namespace }}', 'HyperfTest\Cases', $stub);
        $tableName = $this->input->getArguments();

        $tableName['name'] = $this->unCamelCase($tableName['name']);
        $dbPrefix = \Hyperf\Support\env('DB_PREFIX');
        $result = $this->getTableColumnsComment($dbPrefix.$tableName['name']);

        $key = null;
        $one = '';
        foreach ($result as $k => $column) {
            if($column->Key == 'PRI' && !$key){
                $key = '\''.$column->Field.'\'';
            }
            $pri = $this->convertDbTypeToPhpType($column->Type);
      
            if($column->Key != 'PRI' && $column->Null == 'NO'){
                if($pri == 'integer' || $pri == 'string'){
                    $one .= '\''.$column->Field.'\''.'=>1,';
                }else if ($pri == 'float'){
                    $one .= '\''.$column->Field.'\''.'=>'.'\'1.0\',';
                }else{
                    $one .= '\''.$column->Field.'\''.'=>1,';
                }
            }
        }
        $smollTableName = $this->lcfirst( $tableName['name']);
        $stub = str_replace('{{smollTableName}}', $smollTableName, $stub);
        $tableN = $this->camelCase($tableName['name']);

        $stub = str_replace('{{tableName}}', $tableN, $stub);
        
        $stub = str_replace('{{primaryKey}}', $key, $stub);
        $stub = str_replace('{{one}}', $one, $stub);

        return $stub;
    }

}