<?php

namespace Japool\Genconsole\Console;

use Hyperf\Command\Annotation\Command;

#[Command]
class MakeRequest extends AbstractCrudGenerator
{
    public function __construct()
    {
        parent::__construct('generate:crud-request');
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

    protected function getClassSuffix(): string
    {
        return 'Request';
    }

    protected function getConfigKey(): string
    {
        return 'request';
    }

    protected function buildReplacements(array $context): array
    {
        $saveRules = '';
        $getRules = '';
        $delRules = '';
        $rules = '';
        $messages = '';
        $keyCount = 0;
        
        // 获取主键类型信息
        $priTypeInfo = $this->extractPrimaryKeyType($context);

        // 处理列规则
        foreach ($context['columns'] as $column) {
            // 统计主键数量
            if ($this->isPrimaryKey($column, $context['dbDriver'])) {
                $keyCount++;
            }

            $this->makeRulesArray($column, $context['tableName'], $rules, $messages, $keyCount);
            $this->makeScenesRules($column, $saveRules, $getRules, $delRules, $keyCount);
        }

        $this->makeGetArrayPaginate($rules, $messages, $getRules);

        return [
            '{{ class }}' => $context['camelTableName'] . 'Request',
            '{{ saveRules }}' => $saveRules,
            '{{ delRules }}' => $delRules,
            '{{ getRules }}' => $getRules,
            '{{ saveApi }}' => $this->buildApiPath($context['tableName'], 'save'),
            '{{ delApi }}' => $this->buildApiPath($context['tableName'], 'del'),
            '{{ getApi }}' => $this->buildApiPath($context['tableName'], 'get'),
            '{{ allRules }}' => $rules,
            '{{ messages }}' => $messages,
            '{{ priType }}' => $priTypeInfo['type'],
            '{{ priTypeDefault }}' => $priTypeInfo['default'],
        ];
    }

    /**
     * 提取主键类型信息
     */
    protected function extractPrimaryKeyType(array $context): array
    {
        foreach ($context['columns'] as $column) {
            if ($this->isPrimaryKey($column, $context['dbDriver'])) {
                $dataType = $context['dbDriver'] == 'pgsql' 
                    ? $column->data_type 
                    : $column->Type;
                    
                $phpType = $this->convertDbTypeToPhpType($dataType);
                $type = ($phpType == 'integer') ? 'integer' : 'string';
                
                return [
                    'type' => $type,
                    'default' => "'{$type}'"
                ];
            }
        }
        
        return ['type' => 'string', 'default' => "'string'"];
    }

}