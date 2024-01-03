<?php

namespace App\Lib\Console;

use App\Lib\Console\src\AutoCodeHelp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Hyperf\Command\Command as HyperfCommand;

#[Command]
class DelCrudCodeClass extends HyperfCommand
{
    use AutoCodeHelp;

    protected ContainerInterface $container;

    #[value('repository')]
    protected $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('del:crud-code');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('del:crud-code command');
    }

    public function handle()
    {
        $this->delCurlFileList();
    }
}

