<?php

namespace Japool\Genconsole\Console\src;

/**
 * 验证规则构建 Trait
 * 职责：构建验证规则相关方法（从 AutoCodeHelp 中提取）
 */
trait ValidationRuleBuilderTrait
{
    /**
     * 生成 rule 和 messages
     * @param $column
     * @param $tableName
     * @param $store
     * @param $messages
     * @param $keyCount
     * @param string $dbDriver 数据库驱动类型
     * @return true
     */
    public function makeRulesArray($column, $tableName, &$store, &$messages, $keyCount, $dbDriver = null)
    {
        $dbDriver = $dbDriver ?? \Hyperf\Support\env('DB_DRIVER', 'mysql');

        // 提取列信息
        $columnInfo = $this->extractColumnInfo($column, $dbDriver);
        
        // 跳过时间戳字段
        if (in_array($columnInfo['name'], ['deleted_at', 'created_at', 'updated_at'])) {
            return true;
        }

        // 主键处理
        if ($this->isPrimaryKeyColumn($columnInfo, $keyCount)) {
            $store .= "'{$columnInfo['name']}' => \$this->getKeyRule(),\r";
            $messages .= "'{$columnInfo['name']}.required' => '{$columnInfo['comment']}不能为空',\r";
            $messages .= "'{$columnInfo['name']}.exists' => '{$columnInfo['comment']}必须存在',\r";
            return true;
        }

        // 特殊字段处理
        if ($this->handleSpecialFields($columnInfo['name'], $tableName, $columnInfo['comment'], $store, $messages)) {
            return true;
        }

        // 根据类型生成规则
        $this->generateRulesByType($columnInfo, $store, $messages);

        return true;
    }

    /**
     * 生成 Scenes 规则
     */
    public function makeScenesRules($column, &$saveRules, &$getRules, &$delRules, $keyCount, $dbDriver = null)
    {
        $dbDriver = $dbDriver ?? \Hyperf\Support\env('DB_DRIVER', 'mysql');

        $columnInfo = $this->extractColumnInfo($column, $dbDriver);

        // 跳过时间戳字段
        if (in_array($columnInfo['name'], ['deleted_at', 'created_at', 'updated_at'])) {
            return true;
        }

        // 主键字段
        if ($columnInfo['isPrimaryKey']) {
            $saveRules .= "'{$columnInfo['name']}',\r";
            $getRules .= "'{$columnInfo['name']}',\r";
            if ($keyCount == 1) {
                $delRules .= "'{$columnInfo['name']}',\r";
            }
        } else {
            $saveRules .= "'{$columnInfo['name']}',\r";
            $getRules .= "'{$columnInfo['name']}',\r";
        }

        return true;
    }

    /**
     * 处理生成分页规则
     */
    public function makeGetArrayPaginate(&$store, &$msg, &$getRules)
    {
        $paginationRules = [
            'page' => [
                'rule' => 'required|integer|min:1',
                'messages' => [
                    'required' => '分页参数必须携带',
                    'integer' => '分页参数必须是数字',
                    'min' => '分页参数必须大于0',
                ],
            ],
            'pageSize' => [
                'rule' => 'required|integer|min:1|max:500',
                'messages' => [
                    'required' => '分页参数必须携带',
                    'integer' => '分页参数必须是数字',
                    'min' => '分页参数必须大于0',
                    'max' => '单页参数不能大于500',
                ],
            ],
        ];

        foreach ($paginationRules as $field => $config) {
            $store .= "'{$field}' => '{$config['rule']}',\r";
            
            foreach ($config['messages'] as $rule => $message) {
                $msg .= "'{$field}.{$rule}' => '{$message}',\r";
            }
            
            $getRules .= "'{$field}',\r";
        }

        return true;
    }

    /**
     * 提取列信息
     */
    private function extractColumnInfo($column, string $dbDriver): array
    {
        if ($dbDriver === 'pgsql') {
            return [
                'name' => $column->column_name,
                'type' => $column->data_type,
                'comment' => $column->column_comment ?? '',
                'isPrimaryKey' => $column->is_primary_key === 'YES',
            ];
        }

        return [
            'name' => $column->Field,
            'type' => preg_replace('/\(.*\)/', '', $column->Type),
            'comment' => $column->Comment ?? '',
            'isPrimaryKey' => $column->Key === 'PRI',
        ];
    }

    /**
     * 判断是否为主键列
     */
    private function isPrimaryKeyColumn(array $columnInfo, int $keyCount): bool
    {
        return $columnInfo['isPrimaryKey'] && $keyCount === 1;
    }

    /**
     * 处理特殊字段（手机号、身份证等）
     */
    private function handleSpecialFields(string $name, string $tableName, string $comment, &$store, &$messages): bool
    {
        if (strstr($name, 'phone') || strstr($name, 'mobile')) {
            $store .= "'{$name}' => 'integer|max:22|unique:{$tableName}',\r";
            $messages .= "'{$name}.integer' => '{$comment}必须是数字',\r";
            $messages .= "'{$name}.max' => '{$comment}长度最大22个字符内',\r";
            $messages .= "'{$name}.unique' => '{$comment}已存在手机号,请及时检查',\r";
            return true;
        }

        if (strstr($name, 'id_card')) {
            $store .= "'{$name}' => 'string|id_card|unique:{$tableName}',\r";
            $messages .= "'{$name}.string' => '{$comment}必须是字符串',\r";
            $messages .= "'{$name}.id_card' => '{$comment}身份证格式不正确',\r";
            $messages .= "'{$name}.unique' => '{$comment}已存在身份证号,请及时检查',\r";
            return true;
        }

        return false;
    }

    /**
     * 根据类型生成规则
     */
    private function generateRulesByType(array $columnInfo, &$store, &$messages): void
    {
        $type = $columnInfo['type'];
        $name = $columnInfo['name'];
        $comment = $columnInfo['comment'];

        // 整数类型
        if (in_array($type, ['int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint', 'serial', 'bigserial'])) {
            $store .= "'{$name}' => 'integer',\r";
            $messages .= "'{$name}.integer' => '{$comment}必须是数字',\r";
            return;
        }

        // 浮点数类型
        if (in_array($type, ['float', 'double', 'real', 'double precision', 'decimal', 'numeric'])) {
            $store .= "'{$name}' => 'numeric|decimal:2',\r";
            $messages .= "'{$name}.numeric' => '{$comment}必须是数字参数',\r";
            $messages .= "'{$name}.decimal' => '{$comment}必须是浮点型保留小数',\r";
            return;
        }

        // 字符串类型
        if (in_array($type, ['char', 'varchar', 'character varying', 'text', 'longtext', 'longvarchar'])) {
            $store .= "'{$name}' => 'string',\r";
            $messages .= "'{$name}.string' => '{$comment}必须字符串',\r";
            return;
        }

        // 日期时间类型
        if (in_array($type, ['date', 'datetime', 'timestamp', 'time', 'year'])) {
            $store .= "'{$name}' => 'date',\r";
            $messages .= "'{$name}.date' => '{$comment}必须是日期格式',\r";
            return;
        }

        // JSON 类型
        if (in_array($type, ['json', 'jsonb'])) {
            $store .= "'{$name}' => 'json|nullable',\r";
            $messages .= "'{$name}.string' => '{$comment}必须是json格式',\r";
            return;
        }

        // 枚举类型
        if (in_array($type, ['enum', 'set'])) {
            $store .= "'{$name}' => 'string',\r";
            $messages .= "'{$name}.string' => '{$comment}必须是字符串',\r";
        }
    }
}

