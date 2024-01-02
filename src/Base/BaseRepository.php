<?php

namespace App\Lib\Base;

use App\Lib\Base\src\RepositoryFunction;
use App\Lib\Base\src\RepositoryHelp;
use Hyperf\Config\Annotation\Value;

abstract class BaseRepository
{
    use RepositoryFunction,RepositoryHelp;

    public $model;

    #[value('repository')]
    protected $config;

    public function model(): string
    {
        return $this->config['model'].'\\'.str_replace('Repository', '', class_basename(get_class($this)).'Model');
    }

    public function __construct()
    {
        $this->makeModel();
    }

    public function makeModel()
    {
        return $this->setModel($this->model());
    }

    public function setModel($eloquentModel)
    {
        $this->model = new $eloquentModel;

        return $this->model;
    }
}