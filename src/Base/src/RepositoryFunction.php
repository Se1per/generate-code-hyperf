<?php

namespace App\Lib\Base\src;

trait RepositoryFunction
{

    public function with($with)
    {
        return $this->model->with($with);
    }

    public function whereIn($columns, $value = null)
    {
        return $this->model->whereIn($columns, $value);
    }

    public function where($columns, $value = null)
    {
        return $this->model->where($columns, $value);
    }

    /**
     * 分页
     * @param array|null $data
     * @return $this
     */
    public function skip(array $data = null)
    {
        $data['page'] = $data['page'] ?? 1;

        $data['limit'] = $data['limit'] ?? 10;

        $data['page'] = ($data['page'] - 1) * $data['limit'];

        $this->model = $this->model->skip($data['page'])->take($data['limit']);

        return $this;
    }

    /**
     * 获取一行的值
     * @return mixed
     */
    public function find()
    {
        return $this->model->first();
    }

    /**
     * 获取一行的值
     * @return mixed
     */
    public function first()
    {
        return $this->model->first();
    }

    /**
     * 数据最后一个
     * @param string $latest
     * @return $this
     */
    public function latest(string $latest = 'created_at')
    {
        $this->model = $this->model->latest($latest);

        return $this;
    }

    /**
     * 获取单个值
     * @return mixed
     */
    public function value()
    {
        return $this->model->value();
    }

    /**
     * 获取单列
     * @return mixed
     */
    public function pluck()
    {
        return $this->model->pluck();
    }

    public function get()
    {
        return $this->model->get();
    }

    public function count()
    {
        return $this->model->count();
    }
}