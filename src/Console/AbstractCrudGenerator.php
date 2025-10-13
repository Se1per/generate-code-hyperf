<?php

namespace Japool\Genconsole\Console;

use Japool\Genconsole\Console\Services\DatabaseService;
use Japool\Genconsole\Console\src\StringHelperTrait;
use Japool\Genconsole\Console\src\ExtensionCheckerTrait;
use Japool\Genconsole\Console\src\ValidationRuleBuilderTrait;
use Japool\Genconsole\Console\src\ModelDataBuilderTrait;
use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;
use Hyperf\Devtool\Generator\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Psr\Container\ContainerInterface;

/**
 * CRUD 生成器抽象类
 * 重构后：职责更清晰，使用服务类和 trait
 */
abstract class AbstractCrudGenerator extends GeneratorCommand
{
    use StringHelperTrait;
    use ExtensionCheckerTrait;
    use ValidationRuleBuilderTrait;
    use ModelDataBuilderTrait;

    #[Value('generator')]
    protected $config;

    protected DatabaseService $dbService;
    protected string $dbConnection = 'default';

    // 子类需要实现的抽象方法
    abstract protected function getClassSuffix(): string;
    abstract protected function getConfigKey(): string;
    abstract protected function buildReplacements(array $context): array;

    public function configure()
    {
        parent::configure();
        $this->addOption('db', null, InputOption::VALUE_OPTIONAL, '指定数据库连接配置名称', 'default');
        $this->addOption('db-driver', null, InputOption::VALUE_OPTIONAL, '数据库驱动类型', null);
        $this->addOption('db-prefix', null, InputOption::VALUE_OPTIONAL, '数据库表前缀', null);
    }

    /**
     * 初始化数据库服务
     */
    protected function initializeDatabaseService(): void
    {
        if (!isset($this->dbService)) {
            $container = $this->container ?? \Hyperf\Context\ApplicationContext::getContainer();
            $this->dbService = new DatabaseService(
                $container->get(\Hyperf\Contract\ConfigInterface::class)
            );
        }
    }

    protected function qualifyClass(string $name): string
    {
        $name = $this->input->getArguments();
        // 将表名转换为大驼峰格式，然后拼接后缀
        $name = $this->camelCase($name['name']) . $this->getClassSuffix();

        $namespace = $this->input->getOption('namespace');
        if (empty($namespace)) {
            $namespace = $this->getDefaultNamespace();
        }

        return $namespace . '\\' . $name;
    }

    protected function getDefaultNamespace(): string
    {
        $configKey = $this->getConfigKey();
        return $this->config['general'][$configKey];
    }

    protected function replaceClass(string $stub, $name): string
    {
        $stub = $this->replaceName($stub);
        return parent::replaceClass($stub, $name);
    }

    /**
     * 统一的替换逻辑
     */
    public function replaceName($stub)
    {
        // 构建上下文信息
        $context = $this->buildContext();

        // 获取子类特定的替换规则
        $replacements = $this->buildReplacements($context);

        // 合并通用替换规则
        $replacements = array_merge($this->getCommonReplacements($context), $replacements);

        // 执行批量替换
        return $this->batchReplace($stub, $replacements);
    }

    /**
     * 构建通用上下文信息
     */
    protected function buildContext(): array
    {
        $tableName = $this->input->getArguments();
        $originalTableName = $tableName['name'];
        $tableName['name'] = $this->unCamelCase($tableName['name']);

        // 初始化数据库服务
        $this->initializeDatabaseService();
        
        // 获取数据库配置（优先使用命令行选项）
        $this->dbConnection = $this->input->getOption('db') ?? 'default';
        $this->dbService->initialize($this->dbConnection);
        
        $dbDriver = $this->input->getOption('db-driver') ?? $this->dbService->getDriver();
        $dbPrefix = $this->input->getOption('db-prefix') ?? $this->dbService->getPrefix();

        $fullTableName = $dbPrefix . $tableName['name'];
        $columns = $this->dbService->getTableColumns($fullTableName);
        $primaryKey = $this->dbService->getPrimaryKey($columns);

        return [
            'originalTableName' => $originalTableName,
            'tableName' => $tableName['name'],
            'camelTableName' => $this->camelCase($tableName['name']),
            'lcfirstTableName' => $this->lcfirst($tableName['name']),
            'dbPrefix' => $dbPrefix,
            'dbDriver' => $dbDriver,
            'dbConnection' => $this->dbConnection,
            'fullTableName' => $fullTableName,
            'columns' => $columns,
            'primaryKey' => $primaryKey,
            'tableComment' => $this->dbService->getTableComment($fullTableName),
        ];
    }

    /**
     * 通用替换规则
     */
    protected function getCommonReplacements(array $context): array
    {
        $key = $context['primaryKey'] ? "'{$context['primaryKey']}'" : null;

        return [
            '{{ table }}' => $context['tableName'],
            '{{ camelTable }}' => $context['camelTableName'],
            '{{ class }}' => $context['camelTableName'],
            '{{ primaryKey }}' => $key,
            '{{ key }}' => $context['primaryKey'],
            '{{ namespace }}' => $this->config['general'][$this->getConfigKey()],
            '{{ base }}' => $this->config['general']['base'],
        ];
    }

    /**
     * 批量替换
     */
    protected function batchReplace(string $stub, array $replacements): string
    {
        foreach ($replacements as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }
        return $stub;
    }

    /**
     * 生成 API 路径
     */
    protected function buildApiPath(string $tableName, string $action): string
    {
        return "'api/" . $this->lcfirst($tableName) . "/" . $action . $this->camelCase($tableName) . "Data'";
    }

    /**
     * 判断是否为主键（委托给 DatabaseService）
     */
    protected function isPrimaryKey($column, ?string $driver = null): bool
    {
        $this->initializeDatabaseService();
        $driver = $driver ?? $this->dbService->getDriver();
        return $this->dbService->isPrimaryKey($column, $driver);
    }

    /**
     * 获取列名（委托给 DatabaseService）
     */
    protected function getColumnName($column, ?string $driver = null): string
    {
        $this->initializeDatabaseService();
        $driver = $driver ?? $this->dbService->getDriver();
        return $this->dbService->getColumnName($column, $driver);
    }

    /**
     * 获取列类型（委托给 DatabaseService）
     */
    protected function getColumnType($column, ?string $driver = null): string
    {
        $this->initializeDatabaseService();
        $driver = $driver ?? $this->dbService->getDriver();
        return $this->dbService->getColumnType($column, $driver);
    }

    /**
     * 获取列注释（委托给 DatabaseService）
     */
    protected function getColumnComment($column, ?string $driver = null): string
    {
        $this->initializeDatabaseService();
        $driver = $driver ?? $this->dbService->getDriver();
        return $this->dbService->getColumnComment($column, $driver);
    }

    /**
     * 获取表列信息（委托给 DatabaseService）
     */
    protected function getTableColumnsComment(string $tableName, ?string $connection = null, ?string $dbDriver = null): array
    {
        $this->initializeDatabaseService();
        
        if ($connection) {
            $this->dbService->initialize($connection);
        }
        
        return $this->dbService->getTableColumns($tableName);
    }

    /**
     * 获取表注释（委托给 DatabaseService）
     */
    protected function getTableComment(string $tableName, ?string $connection = null)
    {
        $this->initializeDatabaseService();
        
        if ($connection) {
            $this->dbService->initialize($connection);
        }
        
        return $this->dbService->getTableComment($tableName);
    }
}
