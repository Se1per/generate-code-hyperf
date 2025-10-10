<?php

namespace Japool\Genconsole\Console;

use Japool\Genconsole\Console\src\AutoCodeHelp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;
use Hyperf\Devtool\Generator\GeneratorCommand;

abstract class AbstractCrudGenerator extends GeneratorCommand
{
    use AutoCodeHelp;

    #[Value('generator')]
    protected $config;

    // 子类需要实现的抽象方法
    abstract protected function getClassSuffix(): string;  // 返回类后缀：Controller、Service、Request等
    abstract protected function getConfigKey(): string;    // 返回配置key：controller、service、request等
    abstract protected function buildReplacements(array $context): array; // 返回特定的替换规则

    protected function qualifyClass(string $name): string
    {
        $name = $this->input->getArguments();
        $name = $name['name'] . $this->getClassSuffix();

        $namespace = $this->input->getOption('namespace');
        if (empty($namespace)) {
            $namespace = $this->getDefaultNamespace();
        }

        return $namespace . '\\' . $name;
    }

    protected function getDefaultNamespace(): string
    {
        $configKey = $this->getConfigKey();
        return $this->config['general'][$configKey];
    }

    protected function replaceClass(string $stub, $name): string
    {
        $stub = $this->replaceName($stub);
        return parent::replaceClass($stub, $name);
    }

    /**
     * 统一的替换逻辑
     */
    public function replaceName($stub)
    {
        // 获取通用上下文信息
        $context = $this->buildContext();

        // 获取子类特定的替换规则
        $replacements = $this->buildReplacements($context);

        // 合并通用替换规则
        $replacements = array_merge($this->getCommonReplacements($context), $replacements);

        // 执行批量替换
        return $this->batchReplace($stub, $replacements);
    }

    /**
     * 构建通用上下文信息
     */
    protected function buildContext(): array
    {
        $tableName = $this->input->getArguments();
        $originalTableName = $tableName['name'];
        $tableName['name'] = $this->unCamelCase($tableName['name']);

        $dbPrefix = \Hyperf\Support\env('DB_PREFIX');
        $dbDriver = \Hyperf\Support\env('DB_DRIVER');

        $fullTableName = $dbPrefix . $tableName['name'];
        $columns = $this->getTableColumnsComment($fullTableName);

        return [
            'originalTableName' => $originalTableName,
            'tableName' => $tableName['name'],
            'camelTableName' => $this->camelCase($tableName['name']),
            'lcfirstTableName' => $this->lcfirst($tableName['name']),
            'dbPrefix' => $dbPrefix,
            'dbDriver' => $dbDriver,
            'fullTableName' => $fullTableName,
            'columns' => $columns,
            'primaryKey' => $this->extractPrimaryKey($columns, $dbDriver),
            'tableComment' => $this->getTableComment($fullTableName),
        ];
    }

    /**
     * 提取主键（统一处理 pgsql 和 mysql）
     */
    protected function extractPrimaryKey($columns, string $dbDriver): ?string
    {
        foreach ($columns as $column) {
            if ($dbDriver == 'pgsql') {
                if ($column->is_primary_key == 'YES') {
                    return $column->column_name;
                }
            } else {
                if ($column->Key == 'PRI') {
                    return $column->Field;
                }
            }
        }
        return null;
    }

    /**
     * 通用替换规则
     */
    protected function getCommonReplacements(array $context): array
    {
        $key = $context['primaryKey'] ? "'{$context['primaryKey']}'" : null;

        return [
            '{{ table }}' => $context['tableName'],
            '{{ camelTable }}' => $context['camelTableName'],
            '{{ class }}' => $context['camelTableName'],
            '{{ primaryKey }}' => $key,
            '{{ key }}' => $context['primaryKey'],
            '{{ namespace }}' => $this->config['general'][$this->getConfigKey()],
            '{{ base }}' => $this->config['general']['base'],
        ];
    }

    /**
     * 批量替换
     */
    protected function batchReplace(string $stub, array $replacements): string
    {
        foreach ($replacements as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }
        return $stub;
    }

    /**
     * 生成 API 路径
     */
    protected function buildApiPath(string $tableName, string $action): string
    {
        return "'api/" . $this->lcfirst($tableName) . "/" . $action . $this->camelCase($tableName) . "Data'";
    }
    
    /**
     * 判断是否为主键（统一方法供子类使用）
     */
    protected function isPrimaryKey($column, string $dbDriver): bool
    {
        if ($dbDriver == 'pgsql') {
            return isset($column->is_primary_key) && $column->is_primary_key == 'YES';
        }
        return isset($column->Key) && $column->Key == 'PRI';
    }

    /**
     * 获取列名（统一处理 pgsql 和 mysql 的差异）
     */
    protected function getColumnName($column, string $dbDriver): string
    {
        return $dbDriver == 'pgsql' ? $column->column_name : $column->Field;
    }
}
