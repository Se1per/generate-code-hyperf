<?php

namespace Japool\Genconsole\Base;

use Hyperf\Config\Annotation\Value;
use Hyperf\Context\Context;

abstract class BaseRepository
{
    protected $model;

    #[value('repository')]
    protected $config;

    protected $user;

    public function model(): string
    {
        return $this->config['general']['model'] . '\\' . $this->config['general']['app'] . '\\' . str_replace('Repository', '', class_basename(get_class($this)) . 'Model');
    }

    public function __construct()
    {
        $this->makeModel();

        return $this;
    }

    public function makeModel()
    {
        return $this->setModel($this->model());
    }

    public function setModel($eloquentModel)
    {
        if(!class_exists($eloquentModel)){
            throw new \RuntimeException('model not found');
        }

        return $this->model = new $eloquentModel;
    }

    public function getUserToken(): bool
    {
        $user = Context::get('userToken');

        if ($user) {
            $user = json_decode($user, true);
        }

        $this->user = $user;

        return true;
    }
}