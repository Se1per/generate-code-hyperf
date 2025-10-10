<?php

namespace Japool\Genconsole\Console;

use Hyperf\Command\Annotation\Command;

#[Command]
class MakeController extends AbstractCrudGenerator
{
    protected bool $sw = false;

    public function __construct()
    {
        parent::__construct('generate:crud-controller');
    }

    public function configure()
    {
        $this->setDescription('Create a new controller class');
        parent::configure();
    }

    protected function getStub(): string
    {
        if ($this->isSwaggerExtensionInstalled()) {
            $this->sw = true;
            return __DIR__ . '/stubs/controllerSwagger.stub';
        }
        return __DIR__ . '/stubs/controller.stub';
    }

    protected function getClassSuffix(): string
    {
        return 'Controller';
    }

    protected function getConfigKey(): string
    {
        return 'controller';
    }

    protected function getDefaultNamespace(): string
    {
        return $this->config['general']['controller'] . '\\' . $this->config['general']['app'];
    }

    protected function buildReplacements(array $context): array
    {
        $replacements = [
            '{{ class }}' => $context['camelTableName'] . 'Controller',
            '{{ table }}' => $context['camelTableName'],
            '{{ prefix }}' => $context['lcfirstTableName'],
            '{{ request }}' => $this->config['general']['request'],
            '{{ service }}' => $this->config['general']['service'],
            '{{ server }}' => $this->lcfirst($this->config['general']['app']),
            '{{ namespace }}' => $this->config['general']['controller'] . '\\' . $this->config['general']['app'],
        ];

        // 如果启用了 Swagger，添加 Swagger 相关替换
        if ($this->sw) {
            $swaggerReplacements = $this->buildSwaggerReplacements($context);
            $replacements = array_merge($replacements, $swaggerReplacements);
        }

        return $replacements;
    }

    /**
     * 构建 Swagger 相关的替换内容
     */
    protected function buildSwaggerReplacements(array $context): array
    {
        $tableName = $context['tableName'];
        $tableComment = $context['tableComment'];
        
        // 获取表注释，如果没有则使用表名
        $comment = !empty($tableComment->Comment) ? $tableComment->Comment : $tableName;
        
        return [
            '{{ saveApi }}' => $this->buildApiPath($tableName, 'save'),
            '{{ delApi }}' => $this->buildApiPath($tableName, 'del'),
            '{{ getApi }}' => $this->buildApiPath($tableName, 'get'),
            '{{ comment }}' => $comment,
            '{{ saveTags }}' => "'{$comment}'",
            '{{ delTags }}' => "'{$comment}'",
            '{{ getTags }}' => "'{$comment}'",
            '{{ saveProperties }}' => $this->buildSwaggerProperties($context['columns'], 'save'),
            '{{ delProperties }}' => $this->buildSwaggerProperties($context['columns'], 'del'),
            '{{ getProperties }}' => $this->buildSwaggerProperties($context['columns'], 'get'),
            '{{ response }}' => $this->buildSwaggerResponse($context['columns']),
        ];
    }

    /**
     * 构建 Swagger Properties
     */
    protected function buildSwaggerProperties($columns, string $type): string
    {
        $properties = '';
        
        foreach ($columns as $column) {
            // 跳过时间戳字段
            if (in_array($column->Field, ['deleted_at', 'created_at', 'updated_at'])) {
                continue;
            }

            $columnDefault = !empty($column->Comment) ? $column->Comment : $column->Field;
            $phpType = $this->convertDbTypeToPhpType($column->Type);

            if ($type === 'del') {
                // 删除操作只需要主键
                if ($column->Key == 'PRI') {
                    $properties .= "new SA\Property(property: '{$column->Field}', description: '{$columnDefault}', type: '{$phpType}'),\r";
                }
            } elseif ($type === 'get') {
                // 查询操作使用 QueryParameter
                $properties .= "#[SA\QueryParameter(name: '{$column->Field}', description: '{$columnDefault}', schema: new SA\Schema(type: '{$phpType}'))]\r";
            } else {
                // 保存操作包含所有字段
                $properties .= "new SA\Property(property: '{$column->Field}', description: '{$columnDefault}', type: '{$phpType}'),\r";
            }
        }

        // 查询操作需要添加分页参数
        if ($type === 'get') {
            $properties .= "#[SA\QueryParameter(name: 'pageSize', description: '分页参数', required: true, schema: new SA\Schema(type: 'integer'))]\r";
            $properties .= "#[SA\QueryParameter(name: 'page', description: '分页参数', required: true, schema: new SA\Schema(type: 'integer'))]\r";
        }

        return $properties;
    }

    /**
     * 构建 Swagger Response
     */
    protected function buildSwaggerResponse($columns): string
    {
        $response = '';
        
        foreach ($columns as $column) {
            if (in_array($column->Field, ['deleted_at', 'created_at', 'updated_at'])) {
                continue;
            }
            
            $columnDefault = !empty($column->Comment) ? $column->Comment : $column->Field;
            $response .= '"' . $column->Field . '":"' . $columnDefault . '",';
        }

        return $response;
    }
}