<?php

namespace Japool\Genconsole\Console;

use Japool\Genconsole\Console\src\AutoCodeHelp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class MakeCrudCodeClass extends HyperfCommand
{
    use AutoCodeHelp;

    protected ContainerInterface $container;

    #[value('generate')]
    protected $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('generate:crud-code');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('generate:crud-code command');
    }

    public function handle()
    {
        $argument = $this->input->getArgument('tableName') ?? '';

        $tables = DB::select('SHOW TABLES');
 
        foreach ($tables as $val) {

            $val = array_values(json_decode(json_encode($val), true));
            $tableName = array_shift($val);
            $tableName = str_replace(\Hyperf\Support\env('DB_PREFIX'), '', $tableName);

            if($argument){
                if($tableName != $argument){
                    continue;
                }
            }

            $tableName = $this->camelCase($tableName);
            if ($this->keyWordsBlackList($tableName)) continue;

            # controller
            if (!$this->fileExistsIn($this->config['general']['controller'] . '\\' . $this->config['general']['app'] . '\\' . $tableName . 'Controller')) {
                $this->makeControllerFunc($tableName);
            }
            
            # manager
            if (!$this->fileExistsIn($this->config['general']['manager'] . '\\'.$tableName.'Manager')) {
                $this->makeManagerFunc($tableName);
            }

            # model
            if (!$this->fileExistsIn($this->config['general']['model'] . '\\'.$tableName . 'Model')) {
                $this->makeModelFunc($tableName);
            }

            # request
            if (!$this->fileExistsIn($this->config['general']['request'] . '\\'.$tableName . 'Request')) {
                $this->makeRequestFunc($tableName);
            }

            # service
            if (!$this->fileExistsIn($this->config['general']['service'] . '\\'. $tableName . 'Service')) {
                $this->makeServiceFunc($tableName);
            }

            # repository
            if (!$this->fileExistsIn($this->config['general']['repository'] . '\\'. $tableName . 'Repository')) {
                $this->makeRepositoryFunc($tableName);
            }

            # TestIng
            if ($this->isTestIngExtensionInstalled()) {
                if (!$this->fileExistsIn('App\\Test' . '\\' . $tableName . 'Test')) {
                    $this->makeTestFunc($tableName);
                }
            }
        }
    }

    public function makeControllerFunc($tableName)
    {
        $this->call('generate:crud-controller', array_filter([
            'name' => $tableName,
        ]));
        $this->info('完成生成' . $tableName . '控制器层');
    }

    public function makeManagerFunc($tableName)
    {
        $this->call('generate:crud-manager', array_filter([
            'name' => $tableName,
        ]));
        $this->info('完成生成' . $tableName . '业务层');
    }

    public function makeModelFunc($tableName)
    {
        $this->call('generate:crud-model', array_filter([
            'name' => $tableName,
        ]));
        $this->info('完成生成' . $tableName . '模型层');
    }

    public function makeRequestFunc($tableName)
    {
        $this->call('generate:crud-request', array_filter([
            'name' => $tableName,
        ]));
        $this->info('完成生成' . $tableName . '验证层');
    }

    public function makeServiceFunc($tableName)
    {
        $this->call('generate:crud-service', array_filter([
            'name' => $tableName,
        ]));
        $this->info('完成生成' . $tableName . '服务层');
    }

    public function makeRepositoryFunc($tableName)
    {
        $this->call('generate:crud-repository', array_filter([
            'name' => $tableName,
        ]));
        $this->info('完成生成' . $tableName . '数据层');
    }

    public function makeTestFunc($tableName)
    {
        $this->call('generate:generateTest', array_filter([
            'name' => $tableName,
        ]));
        $this->info('完成生成' . $tableName . '测试实例');
    }

    protected
    function getArguments()
    {
        return [
            ['tableName', InputArgument::OPTIONAL, '表名']
        ];
    }
}

