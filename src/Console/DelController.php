<?php

namespace Japool\Genconsole\Console;

use Japool\Genconsole\Console\src\AutoCodeHelp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;

use Hyperf\Devtool\Generator\GeneratorCommand;


#[Command]
class DelController extends GeneratorCommand
{
    #[value('generate')]
    protected $config;

    use AutoCodeHelp;

    protected $sw = false;

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
        if($this->isSwaggerExtensionInstalled()){
            $this->sw = true;
            return __DIR__ . '/stubs/controllerSwagger.stub';
        }else{
            return __DIR__ . '/stubs/controller.stub';
        }
    }

    protected function getDefaultNamespace(): string
    {
        return $this->config['controller'];
    }

    protected function qualifyClass(string $name): string
    {
        $name = $this->input->getArguments();

        $name = $name['name'].'Controller';

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
        $dbPrefix = env('DB_PREFIX');
        $result = $this->getTableColumnsComment($dbPrefix.$tableName['name']);

        $key = null;

        foreach ($result as $column) {
            if($column->Key == 'PRI'){
                $key = '\''.$column->Field.'\'';
            }
        }

        if($this->sw){
            $saveApi = '\''.'api/'.$this->lcfirst($tableName['name']).'/'.'save'.$this->camelCase($tableName['name']).'Data'.'\'';
            $delApi = '\''.'api/'.$this->lcfirst($tableName['name']).'/'.'del'.$this->camelCase($tableName['name']).'Data'.'\'';
            $getApi = '\''.'api/'.$this->lcfirst($tableName['name']).'/'.'get'.$this->camelCase($tableName['name']).'Data'.'\'';

            $stub = str_replace('{{ saveApi }}', $saveApi, $stub);
            $stub = str_replace('{{ delApi }}', $delApi, $stub);
            $stub = str_replace('{{ getApi }}', $getApi, $stub);

            # saveComment
            $dbPrefix = env('DB_PREFIX');
            $tableComment = $this->getTableComment($dbPrefix.$tableName['name']);
            if(!empty($tableComment->Comment)){
                $stub = str_replace('{{ comment }}', $tableComment->Comment, $stub);

                $saveTags = '\''.$tableComment->Comment.'\'';
                $delTags = '\''.$tableComment->Comment.'\'';
                $getTags = '\''.$tableComment->Comment.'\'';

            }else{
                $stub = str_replace('{{ comment }}', $tableName['name'], $stub);
                $saveTags = '\''.$tableName['name'].'\'';
                $delTags = '\''.$tableName['name'].'\'';
                $getTags = '\''.$tableName['name'].'\'';
            }
            $stub = str_replace('{{ saveTags }}', $saveTags, $stub);
            $stub = str_replace('{{ getTags }}', $getTags, $stub);
            $stub = str_replace('{{ delTags }}', $delTags, $stub);

            # RequestBody
            $getResponse = '';
            $saveProperties = '';
            $delProperties = '';
            $getProperties = '';
            $column_default = '';
            foreach ($result as $column) {
                if ($column->Field == 'deleted_at' || $column->Field == 'created_at' || $column->Field == 'updated_at') {
                    continue;
                }

                if (!empty(isset($column->Comment))) {
                    $column_default = $column->Comment;
                } else {
                    $column_default = $column->Field;
                }

                $pri = $this->convertDbTypeToPhpType($column->Type);

                if($column->Key == 'PRI'){
                    $delProperties .= "new SA\Property(property: '".$column->Field."', description: '".$column_default."', type: '".$pri."'),\r";
                }

                $getProperties .= "new SA\Property(property: '".$column->Field."', description: '".$column_default."', type: '".$pri."'),\r";

                $saveProperties .= "new SA\Property(property: '".$column->Field."', description: '".$column_default."', type: '".$pri."'),\r";

                $getResponse .= '"'.$column->Field.'"'.':'.'"'.$column_default.'"'.",\r";

            }

            $stub = str_replace('{{ saveProperties }}', $saveProperties, $stub);
            $stub = str_replace('{{ delProperties }}', $delProperties, $stub);
            $stub = str_replace('{{ getProperties }}', $getProperties, $stub);
            $stub = str_replace('{{ response }}', $getResponse, $stub);

        }

        $stub = str_replace('{{ primaryKey }}', $key, $stub);

        $stub = str_replace('{{ class }}', $this->camelCase($tableName['name']).'Controller', $stub);

        $stub = str_replace('{{ table }}', $this->camelCase($tableName['name']), $stub);

        $stub = str_replace('{{ prefix }}', $this->lcfirst($tableName['name']), $stub);

        $stub = str_replace('{{ namespace }}', $this->config['controller'], $stub);

        return $stub;
    }
}