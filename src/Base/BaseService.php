<?php

namespace App\Lib\Base;

use Carbon\Carbon;

abstract class BaseService
{
    /**
     * 构造查询条件
     * @param $column string 字段
     * @param $operator string 运算符
     * @param $val array|string 值
     * @param $operatorName string|null 关系默认 and 可选 or
     * @return array|Carbon|mixed|string
     * User: Se1per
     * Date: 2023/8/4 11:07
     */
    public function convertToWhereQuery(string $column, string $operator, array|string $val, string $operatorName = null): array
    {
        if(is_string($val)){
            switch ($val) {
                case 'today':
                    $val = Carbon::today();
                    break;
                case 'yesterday':
                    $val = Carbon::yesterday();
                    break;
                case 'tomorrow':
                    $val = Carbon::tomorrow();
                    break;
            }
        }

        switch ($operator) {
            case 'like':
                return [$column, $operator, '%' . $val . '%', 'or'];
            case 'date':
            case 'day':
            case 'month':
            case 'year':
            case '=':
            case '<>':
            case '>':
            case '<':
            case '<=':
            case '>=':
                if($operatorName) return [$column, $operator, $val,$operatorName];
                return [$column, $operator, $val];
            case 'exists':
            case 'func':
            case 'raw':
                return $val;
            case 'notNull':
                return $column;
            default://in notIn
                return [$column, $val];
        }
    }

}