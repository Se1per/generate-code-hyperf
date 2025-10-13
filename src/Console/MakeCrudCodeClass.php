<?php

namespace Japool\Genconsole\Console;

use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * CRUD 代码生成命令
 * 重构后：职责单一，使用服务类处理具体逻辑
 */
#[Command]
class MakeCrudCodeClass extends BaseCodeCommand
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->setName('generate:crud-code');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('生成 CRUD 代码');
        $this->addArgument('tableName', InputArgument::OPTIONAL, '表名（可选，不填则生成所有表）');
    }

    public function handle()
    {
        $startTime = microtime(true);
        
        // 初始化服务
        $this->initializeServices();
        $this->initializeDatabase();
        
        $targetTable = $this->input->getArgument('tableName') ?? '';
        
        // 显示欢迎横幅
        $this->showWelcome($targetTable);
        
        // 获取并过滤表列表
        $tables = $this->getFilteredTables($targetTable);
        $this->statistics->setTotalTables(count($tables));

        if (empty($tables)) {
            $this->error('❌ 未找到任何数据库表');
            return 0;
        }

        $this->line('');
        
        // 处理每个表
        $this->processTables($tables);

        // 显示总结
        $this->showSummary($startTime);
        
        return 0;
    }

    /**
     * 显示欢迎横幅
     */
    private function showWelcome(?string $tableName): void
    {
        $this->displayService->showWelcomeBanner(
            '🚀 Hyperf CRUD 代码生成器 v2.0 🚀',
            $tableName,
            $this->dbService->getConnection(),
            $this->dbService->getDriver(),
            $this->dbService->getDatabaseName(),
            'cyan'
        );
    }

    /**
     * 处理表列表
     */
    private function processTables(array $tables): void
    {
        $total = count($tables);
        
        foreach ($tables as $index => $tableName) {
            $current = $index + 1;
            
            // 显示进度
            $this->displayService->showTableHeader($tableName, $current, $total);
            
            // 生成所有文件
            $this->generateAllFiles($tableName);
            
            // 生成测试文件
            $this->generateTestFile($tableName);
            
            $this->line('');
        }
    }

    /**
     * 生成所有类型的文件
     */
    private function generateAllFiles(string $tableName): void
    {
        $generators = $this->generatorConfig->getAllGenerators();
        
        foreach ($generators as $generator) {
            $this->generateSingleFile($tableName, $generator);
        }
    }

    /**
     * 生成单个文件
     */
    private function generateSingleFile(string $tableName, array $generator): void
    {
        $configKey = $generator['key'] ?? '';
        $command = $generator['command'] ?? '';
        $suffix = $generator['suffix'] ?? '';
        $description = $generator['description'] ?? '';
        $needsApp = $generator['needsApp'] ?? false;
        
        // 检查文件是否存在（生成前）
        $existsBefore = $this->checkFileExistsByConfig($configKey, $tableName, $suffix, $needsApp);
        
        // 调用子命令生成文件
        $this->callGeneratorCommand($command, $tableName);
        
        // 检查文件是否存在（生成后）
        $existsAfter = $this->checkFileExistsByConfig($configKey, $tableName, $suffix, $needsApp);
        
        // 统计并显示结果
        $className = $this->buildClassName($tableName, $suffix);
        
        if (!$existsBefore && $existsAfter) {
            // 新生成
            $this->statistics->addGenerated($tableName, $description, $className);
            $this->displayService->showOperationMessage('success', $description, "{$className}");
        } elseif ($existsBefore) {
            // 已存在，跳过
            $this->statistics->addSkipped($tableName, $description, $className);
            $this->displayService->showOperationMessage('warning', $description, "{$className} (已存在)");
        }
    }

    /**
     * 生成测试文件
     */
    private function generateTestFile(string $tableName): void
    {
        if (!$this->isTestIngExtensionInstalled()) {
            return;
        }

        $testConfig = $this->generatorConfig->getTestConfig();
        $suffix = $testConfig['suffix'] ?? '';
        $description = $testConfig['description'] ?? '';
        $command = $testConfig['command'] ?? '';
        
        // 构建测试类命名空间
        $className = $this->buildClassName($tableName, $suffix);
        $namespace = 'App\\Test\\' . $className;
        $testFilePath = $this->filePathService->getFilePath($namespace);
        
        // 检查文件是否存在（生成前）
        $existsBefore = file_exists($testFilePath);
        
        // 调用子命令生成文件
        $this->callGeneratorCommand($command, $tableName);
        
        // 检查文件是否存在（生成后）
        $existsAfter = file_exists($testFilePath);
        
        // 统计并显示结果
        if (!$existsBefore && $existsAfter) {
            $this->statistics->addGenerated($tableName, $description, $className);
            $this->displayService->showOperationMessage('success', $description, $className);
        } elseif ($existsBefore) {
            $this->statistics->addSkipped($tableName, $description, $className);
            $this->displayService->showOperationMessage('warning', $description, "{$className} (已存在)");
        }
    }

    /**
     * 显示生成总结
     */
    private function showSummary(float $startTime): void
    {
        $duration = round(microtime(true) - $startTime, 2);
        $statistics = $this->statistics->toArray();
        
        $this->displayService->showGenerateSummary($statistics, $duration);
    }
}
