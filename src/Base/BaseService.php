<?php

namespace App\Lib\Base;

use App\Lib\Base\Interface\JsonCallBackInterface;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;

abstract class BaseService
{
    #[Inject]
    protected JsonCallBackInterface $JsonCallBack;
    #[Inject]
    protected RequestInterface $request;

    public function selectArray($makeData): array
    {
        $sql = [];
        foreach ($makeData as $k => $val) {
            if (isset($val)) {
                if ($k == 'page' && count($sql) <= 2) {
                    $data['page'] = $val;
                    $data['limit'] = $makeData['limit'] ?? 10;
                    $data['page'] = ($data['page'] - 1) * $data['limit'];
                    $sql['skip']['page'] = $data['page'];
                    $sql['skip']['limit'] = $data['limit'];
                }
                switch ($k) {
                    case 'id':
                        if (!is_array($val)) {
                            $sql['where'] = $this->convertToWhereQuery($k, '=', $val);
                        } else {
                            $sql['whereIn'] = $this->convertToWhereQuery($k, 'in', $val);
                        }
                        break;
                    case 'created_at':
                    case 'updated_at':
                        if (is_array($val)) {
                            $sql['whereBetween'] = $this->convertToWhereQuery($k, 'in', $val);
                        } else {
                            $sql['whereDate'] = $this->convertToWhereQuery($k, '=', $val);
                        }
                        break;
                }
            }
        }
        return $sql;
    }

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
        if (is_string($val)) {
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
                if ($operatorName) return [$column, $operator, $val, $operatorName];
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

    /**
     * 返回json
     * @param $status
     * @param $object
     * @return mixed
     */
    public function toJson($status,$object): mixed
    {
        if (!$status) {
            return $this->JsonCallBack->JsonMain(200004, $object);
        }

        return $this->JsonCallBack->JsonMain(200000, '操作成功');
    }

    /**
     * 获取ip
     * @return false|string
     */
    public function getForwardedIp(): false|string
    {
        $getServer = $this->request->getHeaders();

        if (isset($getServer['x-forwarded-for'][0])) {
            return $getServer['x-forwarded-for'][0];
        }

        return false;
    }
}