<?php

namespace Japool\Genconsole\Console;

use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;

#[Command]
class MakeModel extends AbstractCrudGenerator
{
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

    protected function getClassSuffix(): string
    {
        return ''; // Model 不需要后缀
    }

    protected function getConfigKey(): string
    {
        return 'model';
    }

    /**
     * Model 特殊处理：需要直接查询数据库而不是使用 context 的 columns
     */
    protected function buildReplacements(array $context): array
    {
        // Model 需要直接查询以获取所有列信息（包括默认值等）
        $dbPrefix = $context['dbPrefix'];
        $dbDriver = $context['dbDriver'];
        $tableName = $context['tableName'];

        if ($dbDriver == 'pgsql') {
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
                        AND cu.table_name = '{$dbPrefix}{$tableName}'
                ) pk ON c.column_name = pk.column_name
                WHERE 
                    c.table_name = '{$dbPrefix}{$tableName}'
            ";
        } else {
            $sql = "SHOW COLUMNS FROM `{$dbPrefix}{$tableName}`;";
        }

        $result = DB::select($sql);

        $primaryKey = null;
        $fillAble = '';
        $softDeletes = false;
        $keyGet = false;

        // 处理每一列
        foreach ($result as $column) {
            if ($dbDriver == 'pgsql') {
                $this->makeModelDataPgsql($column, $primaryKey, $fillAble, $softDeletes, $keyGet);
            } else {
                $this->makeModelData($column, $primaryKey, $fillAble, $softDeletes, $keyGet);
            }
        }

        // Snowflake 支持
        $useSnowflake = '';
        $snowflakeTrait = '';
        if ($this->isSnowflakeExtensionInstalled()) {
            $useSnowflake = 'use Hyperf\Snowflake\Concern\Snowflake;';
            $snowflakeTrait = 'use Snowflake;';
        }

        // SoftDeletes 支持
        $softDeletesTrait = $softDeletes ? 'use SoftDeletes;' : '';

        return [
            '{{ class }}' => $context['camelTableName'],
            '{{ tableName }}' => $tableName,
            '{{ primaryKey }}' => $primaryKey,
            '{{ fillAble }}' => $fillAble,
            '{{ useSnowflake }}' => $useSnowflake,
            '{{ Snowflake }}' => $snowflakeTrait,
            '{{ SoftDeletes }}' => $softDeletesTrait,
        ];
    }
}