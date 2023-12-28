<?php

namespace App\Lib\Base\src;

use Hyperf\Coroutine\Concurrent;
use Hyperf\DbConnection\Db;

trait RepositoryHelp
{
    public function saveData($data): array
    {
        $createData = '';

        go(function () use ($data, &$createData) {
            try {
                $createData = $this->model->create($data);
            } catch (\Exception $e) {
                return [false, $e->getMessage()];
            }
            return true;
        });

        return [true, $createData];
    }

    public function saveAllData($data): array
    {
        $concurrent = new Concurrent(10);

        Db::beginTransaction();

        foreach ($data as $value){
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

    public function updateData($object, $data): array
    {
        go(function () use ($data, $object) {
            try {
                $object = $object->fill($data);
                $object->save();
            } catch (\Exception $e) {
                return [false, $e->getMessage()];
            }
            return true;
        });

        return [true, $object];
    }

    public function updateAllData($object, $data): array
    {
        $concurrent = new Concurrent(10);
        Db::beginTransaction();
        foreach ($object as $value){
            $concurrent->create(function () use ($value,$data,&$num) {
                try {
                    $value = $value->fill($data);
                    $value->save();
                } catch (\Exception $e) {
                    Db::rollBack();
                    return [false, $e->getMessage()];
                }
                return true;
            });
        }

//        Db::commit();

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

    public function runningSql($data, bool $needToArray = false, string $get = 'get', &$dataCallBack = null)
    {
        foreach ($data as $k => $val) {
            if ($get == 'count' && $k == 'skip') continue;
            if (method_exists($this, $k)) {
                $this->model->$k($val);
            } else {
                throw new \ErrorException("Class Repository object does not have a function Name: " . $k);
            }
        }

        if ($get == 'chunk') {
            $this->$get($dataCallBack);
            return $this;
        }

        $list = $this->model->$get();

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