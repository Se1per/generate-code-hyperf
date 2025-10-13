<?php

namespace Japool\Genconsole\Console\src;

/**
 * 字符串辅助 Trait
 * 职责：字符串转换相关方法
 */
trait StringHelperTrait
{
    /**
     * 小驼峰命名转换
     * @param string $str
     * @return string
     */
    public function camelCase(string $str): string
    {
        $str = ucwords(str_replace(['-', '_'], ' ', $str));
        $str = str_replace(' ', '', $str);
        return ucfirst($str);
    }

    /**
     * 大驼峰命名转换（首字母小写）
     * @param string $str
     * @return string
     */
    public function lcfirst(string $str): string
    {
        $str = ucwords(str_replace(['-', '_'], ' ', $str));
        $str = str_replace(' ', '', $str);
        return lcfirst($str);
    }

    /**
     * 驼峰转下划线命名规则法
     * @param $str
     * @return string
     */
    public function unCamelCase($str): string
    {
        $str = preg_replace('/([A-Z])/', '_$1', $str);
        return strtolower(trim($str, '_'));
    }

    /**
     * 转换数据库数据类型到 PHP 类型
     * @param $dbType
     * @return string|null
     */
    public function convertDbTypeToPhpType($dbType): ?string
    {
        $type = strtolower($dbType);

        if (strpos($type, 'int') !== false || strpos($type, 'serial') !== false) {
            return 'integer';
        }

        if (strpos($type, 'decimal') !== false || strpos($type, 'float') !== false || strpos($type, 'double') !== false || strpos($type, 'numeric') !== false) {
            return 'float';
        }

        if (strpos($type, 'boolean') !== false || strpos($type, 'bool') !== false) {
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

        if (strpos($type, 'date') !== false || strpos($type, 'timestamp') !== false || strpos($type, 'time') !== false) {
            return 'date';
        }

        return null;
    }
}

