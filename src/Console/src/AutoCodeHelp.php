<?php

namespace Japool\Genconsole\Console\src;

use Hyperf\DbConnection\Db;

trait AutoCodeHelp
{
    public function fileExistsIn($file)
    {
        if (is_file($file)) {
            return true;
        }
        return false;
    }

    /**
     * 检测是否安装了 Swagger 扩展
     * @return bool
     */
    function isSwaggerExtensionInstalled(): bool
    {
        $composerLock = file_get_contents(BASE_PATH . '/composer.lock');
        return preg_match('/"name":\s+"hyperf\/swagger"/', $composerLock) === 1;
    }

    /**
     * 检测是否安装了 hyperf 自动化测试扩展
     * @return bool
     */
    function isTestIngExtensionInstalled(): bool
    {
        $composerLock = file_get_contents(BASE_PATH . '/composer.lock');
        return preg_match('/"name":\s+"hyperf\/testing"/', $composerLock) === 1;
    }

    /**
     * 检测是否安装了 Snowflake 扩展
     * @return bool
     */
    function isSnowflakeExtensionInstalled(): bool
    {
        $composerLock = file_get_contents(BASE_PATH . '/composer.lock');
        return preg_match('/"name":\s+"hyperf\/snowflake"/', $composerLock) === 1;
    }

    /**
     * 小驼峰命名转换
     * @param string $str
     * @return string
     * User: Se1per
     * Date: 2023/9/22 17:00
     */
    public function camelCase(string $str): string
    {
        $str = ucwords(str_replace(['-', '_'], ' ', $str));
        // 去除空格，并将第一个字母改为大写
        $str = str_replace(' ', '', $str);
        return ucfirst($str);
    }

    /**
     * 大驼峰命名转换
     * @param string $str
     * @return string
     */
    public function lcfirst(string $str): string
    {
        $str = ucwords(str_replace(['-', '_'], ' ', $str));
        // 去除空格，并将第一个字母改为小写
        $str = str_replace(' ', '', $str);
        return lcfirst($str);
    }

    /**
     * 驼峰转下划线命名规则法
     * @param $str
     * @return string
     * User: Se1per
     * Date: 2023/9/22 17:08
     */
    public function unCamelCase($str): string
    {
        $str = preg_replace('/([A-Z])/', '_$1', $str);
        return strtolower(trim($str, '_'));
    }

    /**
     * 生成rule 和messages
     * @param $column
     * @param $tableName
     * @param $store
     * @param $messages
     * @return true
     */
    public function makeRulesArray($column, $tableName, &$store, &$messages,$keyCount)
    {
        $dbDriver = \Hyperf\Support\env('DB_DRIVER');

        if($dbDriver == 'pgsql'){
            $column_name = $column->column_name;
            $column_type = $column->data_type;
            $column_comment = $column->column_comment;
            $is_primary_key = $column->is_primary_key;
        }else{
            $column_name = $column->Field;
            $column_type = $column->Type;
            $column_comment = $column->Comment;
            $is_primary_key = $column->Key;
        }
        if ($column_name == 'deleted_at' || $column_name == 'created_at' || $column_name == 'updated_at') {
            return true;
        }
 
        if ($is_primary_key == 'PRI' && $keyCount == 1) {
            $store .= '\'' . $column_name . '\'' . '=>' . '$this->getKeyRule(),'. "\r";
            $messages .= '\'' . $column_name . '.required' . '\'' . '=>' . '\'' . $column_comment . '不能为空' . '\'' . ',' . "\r";
            $messages .= '\'' . $column_name . '.exists' . '\'' . '=>' . '\'' . $column_comment . '必须存在' . '\'' . ','. "\r";
            return true;
        }

        if (strstr($column_name, "phone") || strstr($column_name, "mobile")) {
            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'integer|max:22|unique:' . $tableName . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.integer' . '\'' . '=>' . '\'' . $column_comment . '必须是数字' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.max' . '\'' . '=>' . '\'' . $column_comment . '长度最大22个字符内' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.unique' . '\'' . '=>' . '\'' . $column_comment . '已存在手机号,请及时检查' . '\'' . ','. "\r";
            return true;
        }

        if (strstr($column_name, "id_card")) {
            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'string|id_card|unique:' . $tableName . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.string' . '\'' . '=>' . '\'' . $column_comment . '必须是字符串' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.id_card' . '\'' . '=>' . '\'' . $column_comment . '身份证格式不正确' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.unique' . '\'' . '=>' . '\'' . $column_comment . '已存在身份证号,请及时检查' . '\'' . ','. "\r";
            return true;
        }
        
        // 判断字段类型
        if (in_array($column_type, ['int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint', 'serial', 'bigserial'])) {
            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'integer' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.integer' . '\'' . '=>' . '\'' . $column_comment . '必须是数字' . '\'' . ','. "\r";
            return true;
        } elseif (in_array($column_type, ['float', 'double', 'real', 'double precision','decimal', 'numeric'])) {
            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'numeric|decimal:2' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.numeric' . '\'' . '=>' . '\'' . $column_comment . '必须是数字参数' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.decimal' . '\'' . '=>' . '\'' . $column_comment . '必须是浮点型保留小数' . '\'' . ','. "\r";
            return true;
        } elseif (in_array($column_type, ['char', 'varchar','character varying', 'text', 'longtext', 'longvarchar', 'longtext'])) {
            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'string' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.string' . '\'' . '=>' . '\'' . $column_comment . '必须字符串' . '\'' . ','. "\r";
            return true;
        } elseif (strpos($column_type, 'blob') !== false) {
            // 二进制数据类型
            // 你的逻辑代码
            return true;
        } elseif (in_array($column_type, ['date', 'datetime', 'timestamp', 'time', 'year'])) {
            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'date' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.date' . '\'' . '=>' . '\'' . $column_comment . '必须是日期格式' . '\'' . ','. "\r";
            return true;
        } elseif (in_array($column_type, ['json', 'jsonb'])) {
            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'json|nullable ' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.string' . '\'' . '=>' . '\'' . $column_comment . '必须是json格式' . '\'' . ','. "\r";
            return true;
        } elseif (in_array($column_type, ['enum', 'set'])) {
            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'string' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.string' . '\'' . '=>' . '\'' . $column_comment . '必须是字符串' . '\'' . ','. "\r";
            return true;
        }

        return true;
    }

    /**
     * 生成Scenes
     * @param $column
     * @param $saveRules
     * @param $getRules
     * @param $delRules
     * @return true
     */
    public function makeScenesRules($column, &$saveRules, &$getRules, &$delRules,$keyCount)
    {
        $dbDriver = \Hyperf\Support\env('DB_DRIVER');

        if($dbDriver == 'pgsql'){
            $column_name = $column->column_name;
            $is_primary_key = $column->is_primary_key;
        }else{
            $column_name = $column->Field;
            $is_primary_key = $column->Key;
        }

        if ($column_name == 'deleted_at' || $column_name == 'created_at' || $column_name == 'updated_at') {
            return true;
        }
 
        if ($is_primary_key == 'PRI' || $is_primary_key == 'YES') {
            $saveRules .= '\'' . $column_name . '\'' . ','. "\r";
            $getRules .= '\'' . $column_name . '\'' . ','. "\r";
            if($keyCount == 1){
                $delRules .= '\'' . $column_name . '\'' . ','. "\r";
            }
        } else {
            $saveRules .= '\'' . $column_name . '\'' . ','. "\r";
            $getRules .= '\'' . $column_name . '\'' . ','. "\r";
        }

//        $getRules .= '\'' . 'page' . '\'' . ','. "\r";
//        $getRules .= '\'' . 'pageSize' . '\'' . ','. "\r";

        return true;
    }

    /**
     * 生成Model可编辑数据
     * @param mixed $column
     * @param mixed $primaryKey
     * @param mixed $fillAble
     * @param mixed $softDeletes
     * @param mixed $keyGet
     * @return bool
     */
    public function makeModelData($column, &$primaryKey, &$fillAble,&$softDeletes,&$keyGet)
    {
        if($column->Field == 'deleted_at'){
            $softDeletes = true;
        }

        if ($column->Field == 'deleted_at' || $column->Field == 'created_at' || $column->Field == 'updated_at') {
            return true;
        }

        $column_name = $column->Field;

        if(!$keyGet){
            if ($column->Key == 'PRI') {
                $keyGet = true;
                if($primaryKey == null){
                    $primaryKey = '\'' . $column_name . '\'';
                }
                return true;
            }
        }

        $fillAble .= '\'' . $column_name . '\'' . ',';

        return true;
    }

    /**
     * 生成Model可编辑数据 pgsql
     * @param mixed $column
     * @param mixed $primaryKey
     * @param mixed $fillAble
     * @param mixed $softDeletes
     * @param mixed $keyGet
     * @return bool
     */
    public function makeModelDataPgsql($column, &$primaryKey, &$fillAble,&$softDeletes,&$keyGet)
    {
        $column_name = $column->column_name;

        if($column->column_name == 'deleted_at'){
            $softDeletes = true;
        }

        if ($column->column_name == 'deleted_at' || $column->column_name == 'created_at' || $column->column_name == 'updated_at') {
            return true;
        }

        if(!$keyGet){
            if ($column->is_primary_key == 'YES') {
                $keyGet = true;
                if($primaryKey == null){
                    $primaryKey = '\'' . $column_name . '\'';
                }
                return true;
            }
        }
        
        $fillAble .= '\'' . $column_name . '\'' . ',';

        return true;

    }

    /**
     * 处理生成分页
     * @param $store
     * @param $msg
     * @return true
     */
    public function makeGetArrayPaginate(&$store, &$msg,&$getRules)
    {
        $store .= '\'' . 'page' . '\'' . '=>' . '\'' . 'required|integer|min:1' . '\'' . ','. "\r";
        $msg .= '\'' . 'page' . '.required' . '\'' . '=>' . '\'' . '分页参数必须携带' . '\'' . ','. "\r";
        $msg .= '\'' . 'page' . '.integer' . '\'' . '=>' . '\'' . '分页参数必须是数字' . '\'' . ','. "\r";
        $msg .= '\'' . 'page' . '.min' . '\'' . '=>' . '\'' . '分页参数必须大于0' . '\'' . ','. "\r";

        $store .= '\'' . 'pageSize' . '\'' . '=>' . '\'' . 'required|integer|min:1|max:500' . '\'' . ','. "\r";
        $msg .= '\'' . 'pageSize' . '.required' . '\'' . '=>' . '\'' . '分页参数必须携带' . '\'' . ','. "\r";
        $msg .= '\'' . 'pageSize' . '.integer' . '\'' . '=>' . '\'' . '分页参数必须是数字' . '\'' . ','. "\r";
        $msg .= '\'' . 'pageSize' . '.min' . '\'' . '=>' . '\'' . '分页参数必须大于0' . '\'' . ','. "\r";
        $msg .= '\'' . 'pageSize' . '.max' . '\'' . '=>' . '\'' . '单页参数不能大于500' . '\'' . ','. "\r";
        
        $getRules .= '\'' . 'page' . '\'' . ','. "\r";
        $getRules .= '\'' . 'pageSize' . '\'' . ','. "\r";
        return true;
    }

    /**
     * 转换数据库数据类型
     * @param $dbType
     * @return string|null
     * User: Se1per
     * Date: 2023/10/13 10:19
     */
    public function convertDbTypeToPhpType($dbType): ?string
    {
        $type = strtolower($dbType);

        if (strpos($type, 'int') !== false) {
            return 'integer';
        }
        if (strpos($type, 'bigint') !== false) {
            return 'integer';
        }
        if (strpos($type, 'smallint') !== false) {
            return 'integer';
        }

        if (strpos($type, 'decimal') !== false || strpos($type, 'float') !== false || strpos($type, 'double') !== false) {
            return 'float';
        }

        if (strpos($type, 'boolean') !== false) {
            return 'boolean';
        }

        if (strpos($type, 'string') !== false || strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
            return 'string';
        }

        if (strpos($type, 'blob') !== false || strpos($type, 'binary') !== false) {
            return 'binary';
        }

        if (strpos($type, 'json') !== false) {
            return 'json';
        }

        if (strpos($type, 'date') !== false || strpos($type, 'timestamp') !== false) {
            return 'date';
        }

        // 如果数据库字段类型不在上述情况中，返回null

        return null;
    }

    /**
     * 黑名单关键字过滤
     * @param $tableName
     * @return bool|mixed true不生成 false 不处理
     * User: Se1per
     * Date: 2023/10/17 11:05
     */
    public function keyWordsBlackList($tableName): mixed
    {
        $blacklist = $this->config['general']['intermediate_table'];
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
     * @return mixed
     * User: Se1per
     * Date: 2023/10/17 11:10
     */
    public function getTableComment($tableName)
    {
        $sql = "SHOW TABLE STATUS LIKE '{$tableName}';";
        $tableComment = DB::select($sql);
        return $tableComment = array_shift($tableComment);
    }

    /**
     * 获取表内字段详情
     * @param $tableName
     * @return array
     * User: Se1per
     * Date: 2023/10/17 11:17
     */
    public function getTableColumnsComment($tableName)
    {
        $dbDriver = \Hyperf\Support\env('DB_DRIVER');

        if($dbDriver == 'pgsql'){
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
        }else{
            $tableDetails = 'SHOW FULL COLUMNS FROM ' . $tableName;
        }

        return DB::select($tableDetails);
    }

    /**
     * 移除生成
     * @return void
     */
    public function delCurlFileList(): void
    {
        $list = ['controller','repository','services','request','model'];

        foreach ($list as $item)
        {
            $filePath = $this->config[$item];

            if((is_dir($filePath)))
            {
                $handle = opendir($filePath);

                if ($handle) {
                    while (($entry = readdir($handle)) !== FALSE) {
                        if(is_file($filePath.'/'.$entry)){
                            unlink($filePath.'/'.$entry);
                        }
                    }
                }
                $this->info('The '.$item.' folder has been deleted');
            }else{
                $this->error($filePath.'not defined');
            }
        }
    }
}