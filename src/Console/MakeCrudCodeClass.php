<?php

namespace App\Lib\Console;

use App\Lib\Console\src\AutoCodeHelp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Hyperf\Command\Command as HyperfCommand;

#[Command]
class MakeCrudCodeClass extends HyperfCommand
{
    use AutoCodeHelp;

    protected ContainerInterface $container;

    #[value('repository')]
    protected $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('gen:crud-code');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('gen:crud-code command');
    }

    public function handle()
    {
        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $val) {
            $val = array_values(json_decode(json_encode($val), true));
            $tableName = array_shift($val);
            $tableName = str_replace(env('DB_PREFIX'), '', $tableName);
            $tableName = $this->camelCase($tableName);
            if ($this->keyWordsBlackList($tableName)) continue;

            # controller
            if (!$this->fileExistsIn($this->config['general']['controller'] . '\\'.$this->config['general']['app'] .'\\'. $tableName . 'Controller')) {
                $this->makeControllerFunc($tableName);
            }

            # model
            if (!$this->fileExistsIn($this->config['general']['model'] . '\\'.$this->config['general']['app']  . $tableName . 'Model')) {
                $this->makeModelFunc($tableName);
            }

            # request
            if (!$this->fileExistsIn($this->config['general']['request'] . '\\'.$this->config['general']['app']  . '\\' . $tableName . 'Request')) {
                $this->makeRequestFunc($tableName);
            }

            # service
            if (!$this->fileExistsIn($this->config['general']['service'] . '\\'.$this->config['general']['app'] . '\\' . $tableName . 'Service')) {
                $this->makeServiceFunc($tableName);
            }

            # repository
            if (!$this->fileExistsIn($this->config['general']['repository'] . '\\'.$this->config['general']['app']  . '\\' . $tableName . 'Repository')) {
                $this->makeRepositoryFunc($tableName);
            }
        }
    }

    public function makeControllerFunc($tableName)
    {
        $this->call('gen:crud-controller', array_filter([
            'name' => $tableName,
        ]));
        $this->info('完成生成' . $tableName . '控制器层');
    }

    public function makeModelFunc($tableName)
    {
        $this->call('gen:crud-model', array_filter([
            'name' => $tableName,
        ]));
        $this->info('完成生成' . $tableName . '模型层');
    }

    public function makeRequestFunc($tableName)
    {
        $this->call('gen:crud-request', array_filter([
            'name' => $tableName,
        ]));
        $this->info('完成生成' . $tableName . '验证器层');
    }

    public function makeServiceFunc($tableName)
    {
        $this->call('gen:crud-service', array_filter([
            'name' => $tableName,
        ]));
        $this->info('完成生成' . $tableName . '业务逻辑层');
    }

    public function makeRepositoryFunc($tableName)
    {
        $this->call('gen:crud-repository', array_filter([
            'name' => $tableName,
        ]));
        $this->info('完成生成' . $tableName . '数据访问层');
    }

}

