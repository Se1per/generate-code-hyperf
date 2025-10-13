<?php

namespace Japool\Genconsole\Console;

use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * CRUD 代码删除命令
 * 重构后：职责单一，使用服务类处理具体逻辑
 */
#[Command]
class DelCrudCodeClass extends BaseCodeCommand
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->setName('generate:del-crud-code');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('删除 CRUD 代码文件');
        $this->addArgument('tableName', InputArgument::OPTIONAL, '表名（可选，不填则删除所有表）');
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

        // 收集要删除的文件
        $filesToDelete = $this->collectFilesToDelete($tables);

        if (empty($filesToDelete)) {
            $this->warn('⚠️  没有找到任何可删除的文件');
            return 0;
        }

        // 显示将要删除的文件列表
        $this->displayService->showFilesToDelete($filesToDelete);

        // 询问确认
        if (!$this->confirmDeletion(count($filesToDelete))) {
            $this->info('❌ 操作已取消');
            return 0;
        }

        $this->line('');
        
        // 执行删除
        $this->executeDelete($filesToDelete);

        // 显示删除总结
        $this->showSummary($startTime);
        
        return 0;
    }

    /**
     * 显示欢迎横幅
     */
    private function showWelcome(?string $tableName): void
    {
        $this->displayService->showWelcomeBanner(
            '🗑️  Hyperf CRUD 代码删除器 v1.0 🗑️',
            $tableName,
            $this->dbService->getConnection(),
            $this->dbService->getDriver(),
            $this->dbService->getDatabaseName(),
            'red'
        );
    }

    /**
     * 收集要删除的文件
     */
    private function collectFilesToDelete(array $tables): array
    {
        $filesToDelete = [];

        foreach ($tables as $tableName) {
            $files = $this->collectTableFiles($tableName);
            $filesToDelete = array_merge($filesToDelete, $files);
        }

        return $filesToDelete;
    }

    /**
     * 收集单个表的所有文件
     */
    private function collectTableFiles(string $tableName): array
    {
        $files = [];
        
        // 收集普通生成器文件
        $generators = $this->generatorConfig->getAllGenerators();
        
        foreach ($generators as $generator) {
            $fileInfo = $this->checkGeneratorFile($tableName, $generator);
            if ($fileInfo) {
                $files[] = $fileInfo;
            }
        }
        
        // 收集测试文件
        if ($this->isTestIngExtensionInstalled()) {
            $testFileInfo = $this->checkTestFile($tableName);
            if ($testFileInfo) {
                $files[] = $testFileInfo;
            }
        }
        
        return $files;
    }

    /**
     * 检查生成器文件是否存在
     */
    private function checkGeneratorFile(string $tableName, array $generator): ?array
    {
        $configKey = $generator['key'] ?? '';
        $suffix = $generator['suffix'] ?? '';
        $description = $generator['description'] ?? '';
        $needsApp = $generator['needsApp'] ?? false;
        
        $filePath = $this->getFilePathByConfig($configKey, $tableName, $suffix, $needsApp);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        return [
            'table' => $tableName,
            'camelTable' => $this->camelCase($tableName),
            'type' => $description,
            'class' => $this->buildClassName($tableName, $suffix),
            'path' => $filePath,
            'configKey' => $configKey,
        ];
    }

    /**
     * 检查测试文件是否存在
     */
    private function checkTestFile(string $tableName): ?array
    {
        $testConfig = $this->generatorConfig->getTestConfig();
        $suffix = $testConfig['suffix'] ?? '';
        $description = $testConfig['description'] ?? '';
        
        $className = $this->buildClassName($tableName, $suffix);
        $namespace = 'App\\Test\\' . $className;
        $filePath = $this->filePathService->getFilePath($namespace);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        return [
            'table' => $tableName,
            'camelTable' => $this->camelCase($tableName),
            'type' => $description,
            'class' => $className,
            'path' => $filePath,
            'configKey' => 'test',
        ];
    }

    /**
     * 确认删除操作
     */
    private function confirmDeletion(int $fileCount): bool
    {
        $question = new ConfirmationQuestion(
            "<fg=red;options=bold>⚠️  确认删除这 {$fileCount} 个文件吗？此操作不可恢复！(yes/no) [no]: </>",
            false
        );

        return $this->io->askQuestion($question);
    }

    /**
     * 执行删除操作
     */
    private function executeDelete(array $filesToDelete): void
    {
        $total = count($filesToDelete);
        
        foreach ($filesToDelete as $index => $fileInfo) {
            $current = $index + 1;
            
            // 显示进度
            $this->displayService->showTableHeader(
                "删除 {$fileInfo['class']}", 
                $current, 
                $total, 
                'red', 
                '🗑️'
            );
            
            // 执行删除
            $this->deleteSingleFile($fileInfo);
        }
    }

    /**
     * 删除单个文件
     */
    private function deleteSingleFile(array $fileInfo): void
    {
        if (!file_exists($fileInfo['path'])) {
            $this->statistics->addNotFound(
                $fileInfo['table'],
                $fileInfo['type'],
                $fileInfo['class'],
                $fileInfo['path']
            );
            $this->displayService->showOperationMessage('warning', '文件不存在', $fileInfo['class']);
            return;
        }
        
        if ($this->filePathService->deleteFile($fileInfo['path'])) {
            $this->statistics->addDeleted(
                $fileInfo['table'],
                $fileInfo['type'],
                $fileInfo['class'],
                $fileInfo['path']
            );
            $this->displayService->showOperationMessage('success', "已删除 {$fileInfo['type']}", $fileInfo['class']);
        } else {
            $this->statistics->addNotFound(
                $fileInfo['table'],
                $fileInfo['type'],
                $fileInfo['class'],
                $fileInfo['path']
            );
            $this->displayService->showOperationMessage('error', "删除失败 {$fileInfo['type']}", $fileInfo['class']);
        }
    }

    /**
     * 显示删除总结
     */
    private function showSummary(float $startTime): void
    {
        $duration = round(microtime(true) - $startTime, 2);
        $statistics = $this->statistics->toArray();
        
        $this->displayService->showDeleteSummary($statistics, $duration);
    }
}
