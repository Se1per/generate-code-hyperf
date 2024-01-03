<?php

namespace App\Lib\Console;

use App\Lib\Console\src\AutoCodeHelp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;
use Hyperf\DbConnection\Db;
use Hyperf\Devtool\Generator\GeneratorCommand;

#[Command]
class MakeRequest extends GeneratorCommand
{
    use AutoCodeHelp;
    #[value('repository')]
    protected $config;

    public function __construct()
    {
        parent::__construct('gen:crud-request');
    }

    public function configure()
    {
        $this->setDescription('Create a new request class');
        parent::configure();
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/request.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return $this->config['request'];
    }

    protected function qualifyClass(string $name): string
    {
        $name = $this->input->getArguments();

        $name = $name['name'].'Request';

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
        $saveRules = '';
        $getRules = '';
        $delRules = '';

        $rules = '';
        $messages = '';
        $saveApi = '\''.'api/'.$this->lcfirst($tableName['name']).'/'.'save'.$this->camelCase($tableName['name']).'Data'.'\'';
        $delApi = '\''.'api/'.$this->lcfirst($tableName['name']).'/'.'del'.$this->camelCase($tableName['name']).'Data'.'\'';
        $getApi = '\''.'api/'.$this->lcfirst($tableName['name']).'/'.'del'.$this->camelCase($tableName['name']).'Data'.'\'';

        $priType = null;
        $priTypeDefault = null;
        $dbPrefix = env('DB_PREFIX');
        $result = $this->getTableColumnsComment($dbPrefix.$tableName['name']);

        foreach ($result as $column) {
            if ($column->Key == 'PRI') {
                $pri = $this->convertDbTypeToPhpType($column->Type);
                if($pri == 'integer'){
                    $priType = 'integer';
                    $priTypeDefault = '\'integer\'';
                }else{
                    $priType = 'string';
                    $priTypeDefault = '\'string\'';
                }
            }

            $this->makeRulesArray($column,$tableName['name'],$rules,$messages);
            $this->makeScenesRules($column,$saveRules,$getRules,$delRules);
        }

        $this->makeGetArrayPaginate($rules,$messages,$getRules);

        $stub = str_replace('{{ saveRules }}', $saveRules, $stub);
        $stub = str_replace('{{ delRules }}', $delRules, $stub);
        $stub = str_replace('{{ getRules }}', $getRules, $stub);

        $stub = str_replace('{{ saveApi }}', $saveApi, $stub);
        $stub = str_replace('{{ delApi }}', $delApi, $stub);
        $stub = str_replace('{{ getApi }}', $getApi, $stub);
        $stub = str_replace('{{ table }}', $tableName['name'], $stub);
        $stub = str_replace('{{ priType }}', $priType, $stub);
        $stub = str_replace('{{ priTypeDefault }}', $priTypeDefault, $stub);

        $stub = str_replace('{{ allRules }}', $rules, $stub);
        $stub = str_replace('{{ messages }}', $messages, $stub);

        $stub = str_replace('{{ namespace }}', $this->config['request'], $stub);

        $stub = str_replace('{{ class }}', $this->camelCase($tableName['name']), $stub);

        return $stub;
    }
}