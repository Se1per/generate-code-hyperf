<?php

namespace App\Lib\Base\src;

use Hyperf\Coroutine\Concurrent;
use Hyperf\DbConnection\Db;

trait RepositoryHelp
{
    public function saveData($data): array
    {
        try {

            $createData = $this->model->create($data);

        } catch (\Exception $e) {

            return [false, $e->getMessage()];
        }

        return [true, $createData];
    }

    public function saveAllData($data): array
    {
        $concurrent = new Concurrent(10);

        Db::beginTransaction();

        foreach ($data as $value) {
            $concurrent->create(function () use ($value) {
                try {
                    $createData = $this->model->create($value);
                } catch (\Exception $e) {
                    return [false, $e->getMessage()];
                }
                return true;
            });
        }

        Db::commit();

        return [true, '批量新增成功'];
    }

    public function updateData($data, string $key = 'id'): array
    {
        if (is_array($data[$key])) {

            $concurrent = new Concurrent(10);

            $object = $this->getDataInBy($key, $data[$key]);

            foreach ($object as $value) {
                $concurrent->create(function () use ($value, $data, &$num) {
                    try {
                        $value = $value->fill($data);
                        $value->save();
                    } catch (\Exception $e) {
                        return [false, $e->getMessage()];
                    }
                    return true;
                });
            }

            return [true, '更新成功'];

        } else {
            $object = $this->getDataFindBy($key, $data[$key]);

            try {
                $object = $object->fill($data);
                $object->save();
            } catch (\Exception $e) {
                return [false, $e->getMessage()];
            }

            return [true, $object];
        }
    }

    public function updateAllData($object, $data): array
    {
        $concurrent = new Concurrent(10);
        foreach ($object as $value) {
            $concurrent->create(function () use ($value, $data, &$num) {
                try {
                    $value = $value->fill($data);
                    $value->save();
                } catch (\Exception $e) {
                    return [false, $e->getMessage()];
                }
                return true;
            });
        }

        return [true, '批量更新成功'];
    }

    public function deleteData($data): array
    {
        try {

            $this->model->destroy($data);

        } catch (\Exception $e) {

            return [false, $e->getMessage()];
        }

        return [true, '数据已删除'];
    }

    /**
     * 获取单条数据
     * @param $attribute
     * @param $value
     * @param string $symbol
     * @param array $columns
     * @return mixed
     */
    public function getDataFindBy($attribute, $value, string $symbol = '=', array $columns = array('*')): mixed
    {
        return $this->model->where($attribute, $symbol, $value)->first($columns);
    }

    /**
     * 获取数据集
     * @param $attribute
     * @param $value
     * @param string $symbol
     * @param array $columns
     * @return mixed
     */
    public function getDataAllBy($attribute, $value, string $symbol = '=', array $columns = array('*')): mixed
    {
        return $this->model->where($attribute, $symbol, $value)->get($columns);
    }

    /**
     * 获取数据集
     * @param $attribute
     * @param array $value
     * @param array $columns
     * @return mixed
     */
    public function getDataInBy($attribute, array $value, array $columns = array('*')): mixed
    {
        return $this->model->whereIn($attribute, $value)->get($columns);
    }

    /**
     * 执行数据层构造
     * @param array $data
     * @return mixed
     * @throws \ErrorException
     */
    public function getCount(array $data = []): mixed
    {
        return $this->runningSql($data, false, 'count');
    }

    /**
     * 执行数据层构造
     * @param array $data
     * @param bool $needToArray
     * @param string $get get|find
     * @return mixed
     * @throws \ErrorException
     */
    public function getData(array $data = [], string $get = 'get', bool $needToArray = false): mixed
    {
        return $this->runningSql($data, $needToArray, $get);
    }

    /**
     * 执行构造数据层
     * @param $data
     * @param bool $needToArray
     * @param string $get
     * @param $dataCallBack
     * @return mixed
     * @throws \ErrorException
     */
    public function runningSql($data, bool $needToArray = false, string $get = 'get'): mixed
    {
        $object = $this->model;

        foreach ($data as $k => $val) {
            if ($get == 'count' && $k == 'skip') continue;
            if ($k == 'with') {
                $object = $object->$k($val);
                continue;
            }
            if ($k == 'skip') {
//                $val['page'] = $val['page'] ?? 1;
//                $val['limit'] = $val['limit'] ?? 10;
//                $val['page'] = ($val['page'] - 1) * $val['limit'];
                $object = $object->skip($val['page'])->take($val['limit']);
                continue;
            }
            if (is_array($val)) {
                $object = $object->$k(...$val);
            } else {
                $object = $object->$k($val);
            }
        }

        $list = $object->$get();

        switch ($get) {
            case 'find':
                if ($list && $needToArray) $list = $list->toArray();
                break;
            case 'get':
                if ($needToArray) $list = $list->toArray();
                break;
        }

        return $list;
    }

}