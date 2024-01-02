<?php

namespace App\Lib\Console\src;

use Hyperf\DbConnection\Db;
use Hyperf\Swagger\Request\SwaggerRequest;

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
     * 小驼峰命名转换
     * @param string $str
     * @return string
     * User: Se1per
     * Date: 2023/9/22 17:00
     */
    public function camelCase(string $str): string
    {
        $str = ucwords(str_replace(['-', '_'], ' ', $str));
        // 去除空格，并将第一个字母改为小写
        $str = str_replace(' ', '', $str);
        return ucfirst($str);
    }

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
    public function makeRulesArray($column, $tableName, &$store, &$messages)
    {
        if ($column->Field == 'deleted_at' || $column->Field == 'created_at' || $column->Field == 'updated_at') {
            return true;
        }

        $column_name = $column->Field;
        $column_type = $column->Type;

        if (!empty(isset($column->Comment))) {
            $column_default = $column->Comment;
        } else {
            $column_default = $column_name;
        }

        if ($column->Key == 'PRI') {
            $store .= '\'' . $column->Field . '\'' . '=>' . '$this->getKeyRule(),'. "\r";
            $messages .= '\'' . $column->Field . '.required' . '\'' . '=>' . '\'' . $column_default . '不能为空' . '\'' . ',' . "\r";
            $messages .= '\'' . $column->Field . '.integer' . '\'' . '=>' . '\'' . $column_default . '必须是数字' . '\'' . ','. "\r";
            $messages .= '\'' . $column->Field . '.exists' . '\'' . '=>' . '\'' . $column_default . '必须存在' . '\'' . ','. "\r";
            return true;
        }

        if (strstr($column->Field, "phone") || strstr($column->Field, "mobile")) {
            $store .= '\'' . $column->Field . '\'' . '=>' . '\'' . 'integer|max:22|unique:' . $tableName . '\'' . ','. "\r";
            $messages .= '\'' . $column->Field . '.integer' . '\'' . '=>' . '\'' . $column_default . '必须是数字' . '\'' . ','. "\r";
            $messages .= '\'' . $column->Field . '.max' . '\'' . '=>' . '\'' . $column_default . '长度最大22个字符内' . '\'' . ','. "\r";
            $messages .= '\'' . $column->Field . '.unique' . '\'' . '=>' . '\'' . $column_default . '已存在手机号,请及时检查' . '\'' . ','. "\r";
            return true;
        }

        if (strstr($column->Field, "id_card")) {
            $store .= '\'' . $column->Field . '\'' . '=>' . '\'' . 'string|id_card|unique:' . $tableName . '\'' . ','. "\r";
            $messages .= '\'' . $column->Field . '.string' . '\'' . '=>' . '\'' . $column_default . '必须是字符串' . '\'' . ','. "\r";
            $messages .= '\'' . $column->Field . '.id_card' . '\'' . '=>' . '\'' . $column_default . '身份证格式不正确' . '\'' . ','. "\r";
            $messages .= '\'' . $column->Field . '.unique' . '\'' . '=>' . '\'' . $column_default . '已存在身份证号,请及时检查' . '\'' . ','. "\r";
            return true;
        }

        // 判断字段类型
        if (strpos($column_type, 'int') !== false) {

            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'integer' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.integer' . '\'' . '=>' . '\'' . $column_default . '必须是数字' . '\'' . ','. "\r";

            return true;

        } elseif (strpos($column_type, 'decimal') !== false) {
            // 小数类型
            // 你的逻辑代码
        } elseif (strpos($column_type, 'float') !== false) {
            // 浮点数类型
            // 你的逻辑代码
        } elseif (strpos($column_type, 'double') !== false) {
            // 双精度浮点数类型
            // 你的逻辑代码
        } elseif (strpos($column_type, 'char') !== false) {
            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'string' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.string' . '\'' . '=>' . '\'' . $column_default . '必须是字符串' . '\'' . ','. "\r";
            return true;
        } elseif (strpos($column_type, 'varchar') !== false) {
            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'string' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.string' . '\'' . '=>' . '\'' . $column_default . '必须是字符串' . '\'' . ','. "\r";
            return true;
        } elseif (strpos($column_type, 'text') !== false) {
            // 文本类型
            // 你的逻辑代码
        } elseif (strpos($column_type, 'blob') !== false) {
            // 二进制数据类型
            // 你的逻辑代码
            return true;
        } elseif (strpos($column_type, 'date') !== false) {
            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'date_format:Y-m-d' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.date_format' . '\'' . '=>' . '\'' . $column_default . '必须是日期格式' . '\'' . ','. "\r";
            return true;
        } elseif (strpos($column_type, 'datetime') !== false) {
            $store .= '\'' . $column_name . '\'' . '=>' . '\'' . 'date_format:Y-m-d H:i:s' . '\'' . ','. "\r";
            $messages .= '\'' . $column_name . '.date_format' . '\'' . '=>' . '\'' . $column_default . '必须是日期格式' . '\'' . ','. "\r";
            return true;
        } elseif (strpos($column_type, 'timestamp') !== false) {
            // 时间戳类型
            // 你的逻辑代码
            return true;
        } elseif (strpos($column_type, 'enum') !== false) {
            // 枚举类型
            // 你的逻辑代码
            return true;
        } elseif (strpos($column_type, 'set') !== false) {
            // 集合类型
            // 你的逻辑代码
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
    public function makeScenesRules($column, &$saveRules, &$getRules, &$delRules)
    {
        if ($column->Field == 'deleted_at' || $column->Field == 'created_at' || $column->Field == 'updated_at') {
            return true;
        }
        $column_name = $column->Field;

        if ($column->Key == 'PRI') {
            $saveRules .= '\'' . $column_name . '\'' . ','. "\r";
            $getRules .= '\'' . $column_name . '\'' . ','. "\r";
            $delRules .= '\'' . $column_name . '\'' . ','. "\r";
        } else {
            $saveRules .= '\'' . $column_name . '\'' . ','. "\r";
            $getRules .= '\'' . $column_name . '\'' . ','. "\r";
        }

        $getRules .= '\'' . 'page' . '\'' . ','. "\r";
        $getRules .= '\'' . 'limit' . '\'' . ','. "\r";

        return true;
    }

    public function makeModelData($column, &$primaryKey, &$fillAble)
    {
        if ($column->Field == 'deleted_at' || $column->Field == 'created_at' || $column->Field == 'updated_at') {
            return true;
        }

        $column_name = $column->Field;

        if ($column->Key == 'PRI') {
            $primaryKey .= '\'' . $column_name . '\'';
            return true;
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
    public function makeGetArrayPaginate(&$store, &$msg)
    {
        $store .= '\'' . 'page' . '\'' . '=>' . '\'' . 'integer' . '\'' . ','. "\r";
        $msg .= '\'' . 'page' . '.integer' . '\'' . '=>' . '\'' . '分页参数必须是数字' . '\'' . ','. "\r";

        $store .= '\'' . 'limit' . '\'' . '=>' . '\'' . 'integer' . '\'' . ','. "\r";
        $msg .= '\'' . 'limit' . '.integer' . '\'' . '=>' . '\'' . '分页参数必须是数字' . '\'' . ','. "\r";

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
        return array_reduce(config('repository.intermediate_table'), function ($carry, $item) use ($tableName) {
            return $carry || stripos($tableName, $item) !== false;
        }, false);
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
        $dbPrefix = env('DB_PREFIX');
        $tableDetails = 'SHOW FULL COLUMNS FROM ' . $dbPrefix . $tableName;
        return DB::select($tableDetails);
    }
}