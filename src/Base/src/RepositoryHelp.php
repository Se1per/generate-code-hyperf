<?php

namespace App\Lib\Base\src;

use ErrorException;
use Hyperf\Coroutine\Concurrent;
use Hyperf\DbConnection\Db;
use SplQueue;

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
    public function getCount(array $data = [], $model = null): mixed
    {
        return $this->runningSql($data, false, 'count', $model);
    }

    /**
     * 执行数据层构造
     * @param array $data
     * @param bool $needToArray
     * @param string $get get|find
     * @return mixed
     * @throws \ErrorException
     */
    public function getData(array $data = [], string $get = 'get', $model = null, bool $needToArray = false): mixed
    {
        return $this->runningSql($data, $needToArray, $get, $model);
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
    public function runningSql($data, bool $needToArray = false, string $get = 'get', $object = null): mixed
    {
        if (!$object) {
            $object = $this->model;
        }

        foreach ($data as $k => $val) {
            if ($get == 'count' && $k == 'skip') continue;
            if ($k == 'with') {
                $object = $object->$k($val);
                continue;
            }

            if ($k == 'skip') {
                $object = $object->skip($val['page'])->take($val['limit']);
                continue;
            }

            switch ($this->array_depth($val)) {
                case 1;
                    $object = $object->$k(...$val);
                    break;
                case 2;
                    if ($k == 'whereIn') {
                        $object = $object->$k(...$val);
                    } else {
                        $object = $object->$k($val);
                    }
                    break;
                case 3;
                    foreach ($val as $vv) {
                        $object = $object->$k(...$vv);
                    }
                    break;
            }
        }

        $list = $object->$get();

        switch ($get) {
            case 'first':
                if ($list && $needToArray) $list = $list->toArray();
                break;
            case 'get':
                if ($needToArray) $list = $list->toArray();
                break;
        }

        return $list;
    }


    public function array_depth($array): int
    {
        if (!is_array($array)) {
            return 0;
        }

        $depth = 1;
        $queue = new SplQueue();
        array_walk($array, function ($element) use ($queue) {
            if (is_array($element)) {
                $queue->enqueue($element);
            }
        });

        while (!$queue->isEmpty()) {
            $currentLevelSize = $queue->count();
            for ($i = 0; $i < $currentLevelSize; $i++) {
                $current = $queue->dequeue();
                array_walk($current, function ($element) use ($queue) {
                    if (is_array($element)) {
                        $queue->enqueue($element);
                    }
                });
            }
            $depth++;
        }

        return $depth;
    }
}