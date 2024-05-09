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
        parent::__construct('gen:generateBaseCommons');
    }

    public function configure()
    {
        $this->setDescription('Create a new BaseCommons class');
        parent::configure();
    }

    public function handle()
    {
        $this->call('gen:generateBaseController', array_filter([
            'name' => 'BaseController',
        ]));
        $this->info('完成生成' . 'BaseController');

        $this->call('gen:GenerateBaseService', array_filter([
            'name' => 'BaseService',
        ]));
        $this->info('完成生成' . 'BaseService');
        
        $this->call('gen:GenerateBaseRepository', array_filter([
            'name' => 'BaseModel',
        ]));
        $this->info('完成生成' . 'BaseRepository');
        
        $this->call('gen:GenerateRepositoryPackage', array_filter([
            'name' => 'RepositoryPackage',
        ]));
        $this->info('完成生成' . 'RepositoryPackage');
        
        $this->call('gen:generateBaseModel', array_filter([
            'name' => 'BaseModel',
        ]));
        $this->info('完成生成' . 'BaseModel');

        $this->call('gen:generateJsonCallBack', array_filter([
            'name' => 'JsonCallBack',
        ]));
        $this->info('完成生成' . 'JsonCallBack');
        
        $this->call('gen:GenerateJsonCallBackInterface', array_filter([
            'name' => 'JsonCallBackInterface',
        ]));
        $this->info('完成生成' . 'JsonCallBackInterface');
        
        $this->call('gen:GenerateValidationExceptionHandler', array_filter([
            'name' => 'ValidationExceptionHandler',
        ]));
        $this->info('完成生成' . 'ValidationExceptionHandler');
    }

}