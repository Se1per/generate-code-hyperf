<?php

namespace Japool\Genconsole\Console;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Config\Annotation\Value;
use Hyperf\Devtool\Generator\GeneratorCommand;
use Psr\Container\ContainerInterface;

#[Command]
class GenerateBase extends HyperfCommand
{
    protected ContainerInterface $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('generate:generateBaseCommons');
    }

    public function configure()
    {
        $this->setDescription('Create a new BaseCommons class');
        parent::configure();
    }

    public function handle()
    {
        $this->call('generate:generateBaseController', array_filter([
            'name' => 'BaseController',
        ]));
        $this->info('完成生成' . 'BaseController');

        $this->call('generate:GenerateBaseService', array_filter([
            'name' => 'BaseService',
        ]));
        $this->info('完成生成' . 'BaseService');
        
        $this->call('generate:GenerateBaseRepository', array_filter([
            'name' => 'BaseModel',
        ]));
        $this->info('完成生成' . 'BaseRepository');
        
        $this->call('generate:GenerateRepositoryPackage', array_filter([
            'name' => 'RepositoryPackage',
        ]));
        $this->info('完成生成' . 'RepositoryPackage');
        
        $this->call('generate:generateBaseModel', array_filter([
            'name' => 'BaseModel',
        ]));
        $this->info('完成生成' . 'BaseModel');

        $this->call('generate:generateJsonCallBack', array_filter([
            'name' => 'JsonCallBack',
        ]));
        $this->info('完成生成' . 'JsonCallBack');
        
        $this->call('generate:GenerateJsonCallBackInterface', array_filter([
            'name' => 'JsonCallBackInterface',
        ]));
        $this->info('完成生成' . 'JsonCallBackInterface');
        
        $this->call('generate:GenerateValidationExceptionHandler', array_filter([
            'name' => 'ValidationExceptionHandler',
        ]));
        $this->info('完成生成' . 'ValidationExceptionHandler');
    }

}