<?php

namespace Japool\Genconsole\Console;

use Japool\Genconsole\Console\src\AutoCodeHelp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;
use Hyperf\DbConnection\Db;
use Hyperf\Devtool\Generator\GeneratorCommand;


#[Command]
class MakeModel extends GeneratorCommand
{
    use AutoCodeHelp;

    #[value('generate')]
    protected $config;

    public function __construct()
    {
        parent::__construct('generate:crud-model');
    }

    public function configure()
    {
        $this->setDescription('Create a new model class');
        parent::configure();
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/model.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return $this->config['general']['model'];
    }

    protected function qualifyClass(string $name): string
    {
        $name = $this->input->getArguments();

        $name = $name['name'];

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
        $sql = 'SHOW COLUMNS FROM `'.$dbPrefix.$tableName['name'].'`;';
        $result = DB::select($sql);
        $primaryKey = null;
        $fillAble = '';

        $softDeletes = false;
        $keyGet = false;
        foreach ($result as $column) {
            $this->makeModelData($column,$primaryKey,$fillAble,$softDeletes,$keyGet);
        }

        if($this->isSnowflakeExtensionInstalled()){
            $stub = str_replace('{{ useSnowflake }}', 'use Hyperf\Snowflake\Concern\Snowflake;', $stub);
            $stub = str_replace('{{ Snowflake }}', 'use Snowflake;', $stub);
        }else{
            $stub = str_replace('{{ useSnowflake }}', '', $stub);
            $stub = str_replace('{{ Snowflake }}','', $stub);
        }

        if($softDeletes){
            $stub = str_replace('{{ SoftDeletes }}', 'use SoftDeletes;', $stub);
        }else{
            $stub = str_replace('{{ SoftDeletes }}','', $stub);
        }

        $stub = str_replace('{{ tableName }}', $tableName['name'], $stub);
        $stub = str_replace('{{ primaryKey }}', $primaryKey, $stub);
        $stub = str_replace('{{ fillAble }}', $fillAble, $stub);

        $stub = str_replace('{{ class }}', $this->camelCase($tableName['name']), $stub);
        $stub = str_replace('{{ namespace }}', $this->config['general']['model'], $stub);
        $stub = str_replace('{{ base }}', $this->config['general']['base'],$stub);
        
        return $stub;
    }
}