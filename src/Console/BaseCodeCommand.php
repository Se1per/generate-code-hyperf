<?php

namespace Japool\Genconsole\Console;

use Japool\Genconsole\Console\Services\DatabaseService;
use Japool\Genconsole\Console\Services\FilePathService;
use Japool\Genconsole\Console\Services\StatisticsService;
use Japool\Genconsole\Console\Services\ConsoleDisplayService;
use Japool\Genconsole\Console\Services\GeneratorConfigService;
use Japool\Genconsole\Console\src\StringHelperTrait;
use Japool\Genconsole\Console\src\ExtensionCheckerTrait;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Config\Annotation\Value;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputOption;

/**
 * 基础代码命令类
 * 职责：提取公共逻辑，减少代码重复
 */
abstract class BaseCodeCommand extends HyperfCommand
{
    use StringHelperTrait;
    use ExtensionCheckerTrait;

    protected ContainerInterface $container;
    protected DatabaseService $dbService;
    protected FilePathService $filePathService;
    protected StatisticsService $statistics;
    protected ConsoleDisplayService $displayService;
    protected GeneratorConfigService $generatorConfig;
    protected SymfonyStyle $io;

    #[Value('generator')]
    protected $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    /**
     * 初始化服务
     */
    protected function initializeServices(): void
    {
        $this->io = new SymfonyStyle($this->input, $this->output);
        
        // 初始化服务类
        $this->dbService = new DatabaseService(
            $this->container->get(\Hyperf\Contract\ConfigInterface::class)
        );
        
        $this->filePathService = new FilePathService();
        $this->statistics = new StatisticsService();
        $this->displayService = new ConsoleDisplayService($this->output, $this->io);
        $this->generatorConfig = new GeneratorConfigService();
    }

    /**
     * 初始化数据库连接
     */
    protected function initializeDatabase(): void
    {
        $dbConnection = $this->input->getOption('db') ?? 'default';
        
        try {
            $this->dbService->initialize($dbConnection);
        } catch (\InvalidArgumentException $e) {
            $this->error("❌ " . $e->getMessage());
            $this->showAvailableConnections();
            exit(1);
        }
    }

    /**
     * 显示可用的数据库连接
     */
    protected function showAvailableConnections(): void
    {
        $config = $this->container->get(\Hyperf\Contract\ConfigInterface::class);
        $databases = $config->get('databases');
        
        $this->line('');
        $this->line('可用的数据库连接配置：');
        foreach (array_keys($databases) as $key) {
            $this->line("  - {$key}");
        }
    }

    /**
     * 配置命令选项
     */
    public function configure()
    {
        parent::configure();
        $this->addOption('db', null, InputOption::VALUE_OPTIONAL, '指定数据库连接配置名称', 'default');
    }

    /**
     * 获取并过滤表列表
     */
    protected function getFilteredTables(?string $targetTable): array
    {
        $tables = $this->dbService->getAllTables();
        
        if (empty($tables)) {
            return [];
        }

        $filteredTables = [];
        
        foreach ($tables as $tableName) {
            // 过滤指定表名
            if ($targetTable && $tableName !== $targetTable) {
                continue;
            }

            $camelTableName = $this->camelCase($tableName);
            
            // 检查黑名单
            if ($this->isInBlacklist($camelTableName)) {
                $this->warn("⚠️  跳过黑名单表: {$tableName}");
                continue;
            }

            $filteredTables[] = $tableName;
        }

        return $filteredTables;
    }

    /**
     * 检查是否在黑名单中
     */
    protected function isInBlacklist(string $tableName): bool
    {
        $blacklist = $this->config['general']['intermediate_table'] ?? [];
        
        foreach ($blacklist as $keyword) {
            if (stripos($tableName, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 构建文件类名
     */
    protected function buildClassName(string $tableName, string $suffix): string
    {
        $camelTableName = $this->camelCase($tableName);
        return $camelTableName . $suffix;
    }

    /**
     * 构建文件命名空间
     */
    protected function buildFileNamespace(
        string $configKey, 
        string $className, 
        bool $needsApp = false
    ): string {
        $namespace = $this->config['general'][$configKey];
        $appNamespace = $needsApp ? $this->config['general']['app'] : null;
        
        return $this->filePathService->buildClassNamespace($namespace, $className, $appNamespace);
    }

    /**
     * 检查文件是否存在
     */
    protected function checkFileExistsByConfig(
        string $configKey,
        string $tableName,
        string $suffix,
        bool $needsApp = false
    ): bool {
        $className = $this->buildClassName($tableName, $suffix);
        $namespace = $this->buildFileNamespace($configKey, $className, $needsApp);
        
        return $this->filePathService->fileExists($namespace);
    }

    /**
     * 获取文件路径
     */
    protected function getFilePathByConfig(
        string $configKey,
        string $tableName,
        string $suffix,
        bool $needsApp = false
    ): string {
        $className = $this->buildClassName($tableName, $suffix);
        $namespace = $this->buildFileNamespace($configKey, $className, $needsApp);
        
        return $this->filePathService->getFilePath($namespace);
    }

    /**
     * 调用子命令
     */
    protected function callGeneratorCommand(
        string $command,
        string $tableName,
        array $extraOptions = []
    ): void {
        $options = array_merge([
            'name' => $tableName,
            '--db' => $this->dbService->getConnection(),
            '--db-driver' => $this->dbService->getDriver(),
            '--db-prefix' => $this->dbService->getPrefix(),
        ], $extraOptions);
        
        $this->call($command, $options);
    }
}

