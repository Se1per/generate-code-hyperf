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

    #[Value('generator')]
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
        $dbDriver = \Hyperf\Support\env('DB_DRIVER');

        if($dbDriver == 'pgsql'){
            $sql = "
                SELECT 
                    c.column_name,
                    c.data_type,
                    c.is_nullable,
                    c.column_default,
                    col_description(t.oid, a.attnum) AS column_comment,
                    CASE 
                        WHEN pk.column_name IS NOT NULL THEN 'YES'
                        ELSE 'NO'
                    END AS is_primary_key
                FROM 
                    information_schema.columns c
                JOIN 
                    pg_class t ON c.table_name = t.relname
                JOIN 
                    pg_namespace n ON t.relnamespace = n.oid AND n.nspname = c.table_schema
                JOIN 
                    pg_attribute a ON a.attrelid = t.oid AND a.attname = c.column_name
                LEFT JOIN (
                    SELECT 
                        cu.column_name
                    FROM 
                        information_schema.constraint_column_usage cu
                    JOIN 
                        information_schema.table_constraints tc 
                        ON cu.constraint_name = tc.constraint_name
                    WHERE 
                        tc.constraint_type = 'PRIMARY KEY'
                        AND cu.table_name = '".$dbPrefix.$tableName['name']."'
                ) pk ON c.column_name = pk.column_name
                WHERE 
                    c.table_name = '".$dbPrefix.$tableName['name']."'
            ";
        }else{
            $sql = 'SHOW COLUMNS FROM `'.$dbPrefix.$tableName['name'].'`;';
        }

        $result = DB::select($sql);


        $primaryKey = null;
        $fillAble = '';

        $softDeletes = false;
        $keyGet = false;
        foreach ($result as $column) {
            if($dbDriver == 'pgsql'){
                $this->makeModelDataPgsql($column,$primaryKey,$fillAble,$softDeletes,$keyGet);
            }else{
                $this->makeModelData($column,$primaryKey,$fillAble,$softDeletes,$keyGet);
            }

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