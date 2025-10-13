<?php

namespace Japool\Genconsole\Console\src;

use Hyperf\DbConnection\Db;

/**
 * 自动代码辅助 Trait（保留用于向后兼容）
 * 重构说明：原有方法已拆分到专门的 trait 中
 * - StringHelperTrait: 字符串转换方法
 * - ExtensionCheckerTrait: 扩展检测方法
 * - ValidationRuleBuilderTrait: 验证规则构建方法
 * - ModelDataBuilderTrait: Model 数据构建方法
 * 
 * 此文件保留所有原有方法，内部调用新的 trait 方法，确保向后兼容
 */
trait AutoCodeHelp
{
    use StringHelperTrait;
    use ExtensionCheckerTrait;
    use ValidationRuleBuilderTrait;
    use ModelDataBuilderTrait;

    /**
     * 检查文件是否存在
     * @deprecated 使用 FilePathService::fileExists() 替代
     */
    public function fileExistsIn($file): bool
    {
        return is_file($file);
    }

    /**
     * 黑名单关键字过滤
     * @param $tableName
     * @return bool|mixed true不生成 false 不处理
     */
    public function keyWordsBlackList($tableName): mixed
    {
        $blacklist = $this->config['general']['intermediate_table'] ?? [];
        
        foreach ($blacklist as $keyword) {
            if (stripos($tableName, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 根据表名获取表详情
     * @param $tableName
     * @param string $connection 数据库连接名称
     * @return mixed
     * @deprecated 使用 DatabaseService::getTableComment() 替代
     */
    public function getTableComment($tableName, $connection = null)
    {
        $sql = "SHOW TABLE STATUS LIKE '{$tableName}'";
        $db = $connection ? DB::connection($connection) : DB::connection();
        $tableComment = $db->select($sql);
        return array_shift($tableComment);
    }

    /**
     * 获取表内字段详情
     * @param $tableName
     * @param string $connection 数据库连接名称
     * @param string $dbDriver 数据库驱动类型
     * @return array
     * @deprecated 使用 DatabaseService::getTableColumns() 替代
     */
    public function getTableColumnsComment($tableName, $connection = null, $dbDriver = null)
    {
        $dbDriver = $dbDriver ?? \Hyperf\Support\env('DB_DRIVER', 'mysql');

        if ($dbDriver === 'pgsql') {
            $tableDetails = "
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
        } else {
            $tableDetails = 'SHOW FULL COLUMNS FROM ' . $tableName;
        }

        $db = $connection ? DB::connection($connection) : DB::connection();
        return $db->select($tableDetails);
    }

    /**
     * 移除生成（已废弃的方法，保留用于兼容）
     * @deprecated 使用 DelCrudCodeClass 命令替代
     */
    public function delCurlFileList(): void
    {
        $list = ['controller', 'repository', 'services', 'request', 'model'];

        foreach ($list as $item) {
            $filePath = $this->config[$item] ?? null;

            if ($filePath && is_dir($filePath)) {
                $handle = opendir($filePath);

                if ($handle) {
                    while (($entry = readdir($handle)) !== false) {
                        if (is_file($filePath . '/' . $entry)) {
                            unlink($filePath . '/' . $entry);
                        }
                    }
                    closedir($handle);
                }
                
                if (method_exists($this, 'info')) {
                    $this->info('The ' . $item . ' folder has been deleted');
                }
            } else {
                if (method_exists($this, 'error')) {
                    $this->error($filePath . ' not defined');
                }
            }
        }
    }
}
