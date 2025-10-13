<?php

namespace Japool\Genconsole\Console\Services;

use Hyperf\Contract\ConfigInterface;
use Hyperf\DbConnection\Db;

/**
 * 数据库操作服务
 * 职责：处理所有数据库相关操作
 */
class DatabaseService
{
    private array $dbConfig = [];
    private string $dbConnection = 'default';

    public function __construct(
        private ConfigInterface $config
    ) {
    }

    /**
     * 初始化数据库连接
     */
    public function initialize(string $connectionName): void
    {
        $this->dbConnection = $connectionName;
        $databases = $this->config->get('databases');
        
        if (!isset($databases[$this->dbConnection])) {
            throw new \InvalidArgumentException("数据库配置 '{$this->dbConnection}' 不存在！");
        }
        
        $this->dbConfig = $databases[$this->dbConnection];
    }

    /**
     * 获取数据库配置
     */
    public function getConfig(): array
    {
        return $this->dbConfig;
    }

    /**
     * 获取连接名称
     */
    public function getConnection(): string
    {
        return $this->dbConnection;
    }

    /**
     * 获取数据库驱动
     */
    public function getDriver(): string
    {
        return $this->dbConfig['driver'] ?? 'mysql';
    }

    /**
     * 获取数据库前缀
     */
    public function getPrefix(): string
    {
        return $this->dbConfig['prefix'] ?? '';
    }

    /**
     * 获取数据库名称
     */
    public function getDatabaseName(): string
    {
        return $this->dbConfig['database'] ?? '';
    }

    /**
     * 获取所有表名
     */
    public function getAllTables(): array
    {
        $driver = $this->getDriver();
        $prefix = $this->getPrefix();

        $sql = $this->getTableListSql($driver);
        $tables = DB::connection($this->dbConnection)->select($sql);

        return $this->extractTableNames($tables, $prefix);
    }

    /**
     * 获取表字段详情
     */
    public function getTableColumns(string $tableName): array
    {
        $driver = $this->getDriver();
        $sql = $this->getColumnsSql($tableName, $driver);
        
        return DB::connection($this->dbConnection)->select($sql);
    }

    /**
     * 获取表注释
     */
    public function getTableComment(string $tableName): mixed
    {
        $driver = $this->getDriver();
        
        if ($driver === 'pgsql') {
            // PostgreSQL 获取表注释
            $sql = "SELECT obj_description('{$tableName}'::regclass) as comment";
        } else {
            // MySQL 获取表注释
            $sql = "SHOW TABLE STATUS LIKE '{$tableName}'";
        }
        
        $result = DB::connection($this->dbConnection)->select($sql);
        return $result ? array_shift($result) : null;
    }

    /**
     * 获取主键字段名
     */
    public function getPrimaryKey(array $columns): ?string
    {
        $driver = $this->getDriver();
        
        foreach ($columns as $column) {
            if ($this->isPrimaryKey($column, $driver)) {
                return $this->getColumnName($column, $driver);
            }
        }
        
        return null;
    }

    /**
     * 判断是否为主键
     */
    public function isPrimaryKey($column, ?string $driver = null): bool
    {
        $driver = $driver ?? $this->getDriver();
        
        if ($driver === 'pgsql') {
            return isset($column->is_primary_key) && $column->is_primary_key === 'YES';
        }
        
        return isset($column->Key) && $column->Key === 'PRI';
    }

    /**
     * 获取列名（兼容不同数据库）
     */
    public function getColumnName($column, ?string $driver = null): string
    {
        $driver = $driver ?? $this->getDriver();
        
        return $driver === 'pgsql' ? $column->column_name : $column->Field;
    }

    /**
     * 获取列类型（兼容不同数据库）
     */
    public function getColumnType($column, ?string $driver = null): string
    {
        $driver = $driver ?? $this->getDriver();
        
        if ($driver === 'pgsql') {
            return $column->data_type;
        }
        
        // MySQL: 移除括号部分，如 int(11) -> int
        return preg_replace('/\(.*\)/', '', $column->Type);
    }

    /**
     * 获取列注释（兼容不同数据库）
     */
    public function getColumnComment($column, ?string $driver = null): string
    {
        $driver = $driver ?? $this->getDriver();
        
        return $driver === 'pgsql' 
            ? ($column->column_comment ?? '') 
            : ($column->Comment ?? '');
    }

    /**
     * 获取表列表的SQL（根据数据库类型）
     */
    private function getTableListSql(string $driver): string
    {
        if ($driver === 'pgsql') {
            return "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
        }
        
        return 'SHOW TABLES';
    }

    /**
     * 获取字段列表的SQL（根据数据库类型）
     */
    private function getColumnsSql(string $tableName, string $driver): string
    {
        if ($driver === 'pgsql') {
            return "
                SELECT 
                    c.column_name,
                    c.column_default,
                    c.is_nullable,
                    c.data_type,
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
                        AND cu.table_name = '{$tableName}'
                ) pk ON c.column_name = pk.column_name
                WHERE 
                    c.table_name = '{$tableName}'
            ";
        }
        
        return "SHOW FULL COLUMNS FROM `{$tableName}`";
    }

    /**
     * 从查询结果中提取表名
     */
    private function extractTableNames(array $tables, string $prefix): array
    {
        $tableNames = [];
        
        foreach ($tables as $table) {
            $tableArray = array_values(json_decode(json_encode($table), true));
            $tableName = array_shift($tableArray);
            $tableName = str_replace($prefix, '', $tableName);
            $tableNames[] = $tableName;
        }
        
        return $tableNames;
    }
}

