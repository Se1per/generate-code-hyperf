<?php

namespace Japool\Genconsole\Console;

use Hyperf\Command\Annotation\Command;

#[Command]
class GenerateTest extends AbstractCrudGenerator
{
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

    protected function getClassSuffix(): string
    {
        return 'ControllerTest';
    }

    protected function getConfigKey(): string
    {
        return 'test';
    }

    protected function buildReplacements(array $context): array
    {
        $primaryKeyValue = $this->buildPrimaryKeyValue($context);
        $requiredFields = $this->buildRequiredFields($context);

        return [
            '{{ class }}' => $context['camelTableName'] . 'ControllerTest',
            '{{tableName}}' => $context['camelTableName'],
            '{{smollTableName}}' => $context['lcfirstTableName'],
            '{{primaryKey}}' => "'{$context['primaryKey']}'",
            '{{one}}' => $requiredFields,
        ];
    }

    /**
     * 构建主键值（用于测试数据）
     */
    protected function buildPrimaryKeyValue(array $context): string
    {
        foreach ($context['columns'] as $column) {
            if ($this->isPrimaryKey($column, $context['dbDriver'])) {
                $dataType = $context['dbDriver'] == 'pgsql' 
                    ? $column->data_type 
                    : $column->Type;
                    
                $phpType = $this->convertDbTypeToPhpType($dataType);
                $columnName = $context['dbDriver'] == 'pgsql' 
                    ? $column->column_name 
                    : $column->Field;
                
                if ($phpType == 'integer') {
                    return "'{$columnName}' => 1";
                }
                return "'{$columnName}' => '1'";
            }
        }
        return '';
    }

    /**
     * 构建必填字段（用于测试数据）
     */
    protected function buildRequiredFields(array $context): string
    {
        $fields = '';
        
        foreach ($context['columns'] as $column) {
            if ($context['dbDriver'] == 'pgsql') {
                // PostgreSQL
                if ($column->is_primary_key == 'YES') {
                    $phpType = $this->convertDbTypeToPhpType($column->data_type);
                    if ($phpType == 'integer') {
                        $fields .= "'{$column->column_name}' => 1,";
                    } else {
                        $fields .= "'{$column->column_name}' => '1',";
                    }
                }
            } else {
                // MySQL
                if ($column->Key != 'PRI' && $column->Null == 'NO') {
                    $phpType = $this->convertDbTypeToPhpType($column->Type);
                    
                    if ($phpType == 'integer') {
                        $fields .= "'{$column->Field}' => 1,";
                    } else if ($phpType == 'float') {
                        $fields .= "'{$column->Field}' => '1.0',";
                    } else if ($phpType == 'string') {
                        $fields .= "'{$column->Field}' => 1,";
                    } else {
                        $fields .= "'{$column->Field}' => 1,";
                    }
                }
            }
        }
        
        return $fields;
    }

}