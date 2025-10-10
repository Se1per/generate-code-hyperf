<?php

namespace Japool\Genconsole\Console;

use Japool\Genconsole\Console\src\AutoCodeHelp;
use Hyperf\Command\Annotation\Command;
use Hyperf\Config\Annotation\Value;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use function Hyperf\Support\env;

#[Command]
class DelCrudCodeClass extends HyperfCommand
{
    use AutoCodeHelp;

    protected ContainerInterface $container;

    #[Value('generator')]
    protected $config;

    /**
     * 文件配置列表
     */
    protected array $fileConfigs = [
        ['controller', 'Controller', '控制器层', true],
        ['manager', 'Manager', '业务层', false],
        ['model', '', '模型层', false],  // Model 不需要后缀
        ['request', 'Request', '验证层', false],
        ['service', 'Service', '服务层', false],
        ['repository', 'Repository', '数据层', false],
        ['test', 'ControllerTest', '测试实例', false, true],  // 最后一个参数表示是测试文件
    ];

    /**
     * 删除统计
     */
    protected array $statistics = [
        'deleted' => [],
        'notFound' => [],
        'total_tables' => 0,
        'total_deleted' => 0,
        'total_notFound' => 0,
    ];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('del:crud-code');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('删除 CRUD 代码文件');
    }

    public function handle()
    {
        $startTime = microtime(true);
        $argument = $this->input->getArgument('tableName') ?? '';
        
        // 显示欢迎横幅
        $this->showWelcomeBanner($argument);
        
        $tables = $this->getAllTables();
        $this->statistics['total_tables'] = count($tables);

        if (empty($tables)) {
            $this->error('❌ 未找到任何数据库表');
            return;
        }

        // 收集要删除的文件
        $filesToDelete = $this->collectFilesToDelete($tables, $argument);

        if (empty($filesToDelete)) {
            $this->warn('⚠️  没有找到任何可删除的文件');
            return;
        }

        // 显示将要删除的文件列表
        $this->showFilesToDelete($filesToDelete);

        // 询问确认
        if (!$this->confirmDeletion(count($filesToDelete))) {
            $this->info('❌ 操作已取消');
            return;
        }

        $this->line('');
        
        // 执行删除
        $this->executeDelete($filesToDelete);

        // 显示删除总结
        $this->showSummary($startTime);
    }

    protected function showWelcomeBanner(?string $tableName): void
    {
        $this->line('');
        $this->line('<fg=red>╔═══════════════════════════════════════════════════════════════╗</>');
        $this->line('<fg=red>║</> <fg=bright-white;options=bold>         🗑️  Hyperf CRUD 代码删除器 v1.0 🗑️               </><fg=red>║</>');
        $this->line('<fg=red>╚═══════════════════════════════════════════════════════════════╝</>');
        $this->line('');
        
        if ($tableName) {
            $this->info("📋 目标表: <fg=bright-white;options=bold>{$tableName}</>");
        } else {
            $this->info("📋 模式: <fg=bright-white;options=bold>批量删除所有表的文件</>");
        }
        
        $dbDriver = env('DB_DRIVER', 'mysql');
        $this->comment("🗄️  数据库: <fg=bright-white>{$dbDriver}</>");
        $this->line('');
    }

    protected function getAllTables(): array
    {
        $dbDriver = env('DB_DRIVER');
        $dbPrefix = env('DB_PREFIX');

        if ($dbDriver == 'pgsql') {
            $tables = DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\'');
        } else {
            $tables = DB::select('SHOW TABLES');
        }

        $tableNames = [];
        foreach ($tables as $val) {
            $val = array_values(json_decode(json_encode($val), true));
            $tableName = array_shift($val);
            $tableName = str_replace($dbPrefix, '', $tableName);
            $tableNames[] = $tableName;
        }

        return $tableNames;
    }

    /**
     * 收集要删除的文件
     */
    protected function collectFilesToDelete(array $tables, ?string $targetTable): array
    {
        $filesToDelete = [];

        foreach ($tables as $tableName) {
            // 过滤指定表名
            if ($targetTable && $tableName !== $targetTable) {
                continue;
            }

            $camelTableName = $this->camelCase($tableName);
            
            // 检查是否在黑名单中
            if ($this->keyWordsBlackList($camelTableName)) {
                continue;
            }

            // 检查每种类型的文件是否存在
            foreach ($this->fileConfigs as $config) {
                [$configKey, $suffix, $description, $needsApp, $isTest] = array_pad($config, 5, false);
                
                $filePath = $this->getFilePath($configKey, $camelTableName, $suffix, $needsApp, $isTest);
                
                if ($filePath && file_exists($filePath)) {
                    $filesToDelete[] = [
                        'table' => $tableName,
                        'camelTable' => $camelTableName,
                        'type' => $description,
                        'class' => $camelTableName . $suffix,
                        'path' => $filePath,
                        'configKey' => $configKey,
                    ];
                }
            }
        }

        return $filesToDelete;
    }

    /**
     * 获取文件路径
     */
    protected function getFilePath(string $configKey, string $tableName, string $suffix, bool $needsApp, bool $isTest = false): ?string
    {
        if ($isTest) {
            // 测试文件特殊处理
            if (!$this->isTestIngExtensionInstalled()) {
                return null;
            }
            $namespace = 'App\\Test';
        } else {
            $namespace = $this->config['general'][$configKey];
            
            if ($needsApp) {
                $namespace .= '\\' . $this->config['general']['app'];
            }
        }
        
        $className = $namespace . '\\' . $tableName . $suffix;
        
        // 转换命名空间为文件路径
        $relativePath = str_replace('\\', '/', $className);
        $relativePath = str_replace('App/', 'app/', $relativePath);
        $filePath = BASE_PATH . '/' . $relativePath . '.php';
        
        return $filePath;
    }

    /**
     * 显示将要删除的文件列表
     */
    protected function showFilesToDelete(array $files): void
    {
        $this->line('<fg=red;options=bold>⚠️  以下文件将被删除：</>');
        $this->line('');

        $table = new Table($this->output);
        $table->setStyle('box');
        $table->setHeaders([
            '<fg=red>#</>',
            '<fg=red>表名</>',
            '<fg=red>类型</>',
            '<fg=red>类名</>',
        ]);

        $rows = [];
        foreach ($files as $index => $file) {
            $rows[] = [
                '<fg=red>' . ($index + 1) . '</>',
                '<fg=yellow>' . $file['table'] . '</>',
                '<fg=yellow>' . $file['type'] . '</>',
                '<fg=yellow>' . $file['class'] . '</>',
            ];
        }

        $table->setRows($rows);
        $table->render();
        
        $this->line('');
        $this->warn("⚠️  共 " . count($files) . " 个文件将被删除");
        $this->line('');
    }

    /**
     * 确认删除操作
     */
    protected function confirmDeletion(int $fileCount): bool
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "<fg=red;options=bold>⚠️  确认删除这 {$fileCount} 个文件吗？此操作不可恢复！(yes/no) [no]: </>",
            false
        );

        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * 执行删除操作
     */
    protected function executeDelete(array $filesToDelete): void
    {
        $totalFiles = count($filesToDelete);
        
        foreach ($filesToDelete as $index => $fileInfo) {
            $current = $index + 1;
            $percentage = round(($current / $totalFiles) * 100);
            $progressBar = $this->createProgressBar($percentage);
            
            $this->line("<fg=red>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>");
            $this->line("<fg=bright-white;options=bold>🗑️  [{$current}/{$totalFiles}] 删除文件</> {$progressBar} <fg=yellow>{$percentage}%</>");
            $this->line("<fg=red>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>");
            
            if (file_exists($fileInfo['path'])) {
                if (unlink($fileInfo['path'])) {
                    $this->statistics['deleted'][] = $fileInfo;
                    $this->statistics['total_deleted']++;
                    $this->line("   <fg=green>✓</> 已删除 {$fileInfo['type']}: <fg=gray>{$fileInfo['class']}</>");
                } else {
                    $this->statistics['notFound'][] = $fileInfo;
                    $this->statistics['total_notFound']++;
                    $this->line("   <fg=red>✗</> 删除失败 {$fileInfo['type']}: <fg=red>{$fileInfo['class']}</>");
                }
            } else {
                $this->statistics['notFound'][] = $fileInfo;
                $this->statistics['total_notFound']++;
                $this->line("   <fg=yellow>⊘</> 文件不存在 {$fileInfo['type']}: <fg=gray>{$fileInfo['class']}</>");
            }
        }
    }

    protected function createProgressBar(int $percentage): string
    {
        $filled = (int) ($percentage / 5);
        $empty = 20 - $filled;
        
        return '<fg=red>' . str_repeat('█', $filled) . '</>' . 
               '<fg=gray>' . str_repeat('░', $empty) . '</>';
    }

    /**
     * 显示删除总结
     */
    protected function showSummary(float $startTime): void
    {
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->line('');
        $this->line('<fg=red>╔═══════════════════════════════════════════════════════════════╗</>');
        $this->line('<fg=red>║</> <fg=bright-white;options=bold>                    📊 删除总结                          </><fg=red>║</>');
        $this->line('<fg=red>╚═══════════════════════════════════════════════════════════════╝</>');
        $this->line('');

        // 创建统计表格
        $table = new Table($this->output);
        $table->setStyle('box-double');
        $table->setHeaders([
            '<fg=bright-white;options=bold>统计项</>',
            '<fg=bright-white;options=bold>数量</>',
        ]);

        $totalFiles = $this->statistics['total_deleted'] + $this->statistics['total_notFound'];

        $table->setRows([
            ['<fg=cyan>处理表数</>', '<fg=bright-white>' . $this->statistics['total_tables'] . '</>'],
            ['<fg=green>成功删除</>', '<fg=green;options=bold>' . $this->statistics['total_deleted'] . '</>'],
            ['<fg=red>失败/不存在</>', '<fg=red>' . $this->statistics['total_notFound'] . '</>'],
            new TableSeparator(),
            ['<fg=bright-white;options=bold>总文件数</>', '<fg=bright-white;options=bold>' . $totalFiles . '</>'],
            ['<fg=bright-white;options=bold>耗时</>', '<fg=bright-white;options=bold>' . $duration . ' 秒</>'],
        ]);

        $table->render();
        
        // 如果有删除的文件，显示详细列表
        if (!empty($this->statistics['deleted'])) {
            $this->line('');
            $this->line('<fg=green;options=bold>✓ 成功删除的文件：</>');
            $this->showFileList($this->statistics['deleted'], 'green');
        }

        // 如果有失败的文件
        if (!empty($this->statistics['notFound'])) {
            $this->line('');
            $this->line('<fg=red;options=bold>✗ 删除失败或不存在的文件：</>');
            $this->showFileList($this->statistics['notFound'], 'red');
        }

        $this->line('');
        
        if ($this->statistics['total_deleted'] > 0) {
            $this->line('<fg=green;options=bold>🎉 删除完成！成功删除 ' . $this->statistics['total_deleted'] . ' 个文件</>');
        } else {
            $this->line('<fg=yellow;options=bold>ℹ️  没有文件被删除</>');
        }
        
        $this->line('');
    }

    /**
     * 显示文件列表
     */
    protected function showFileList(array $files, string $color): void
    {
        $table = new Table($this->output);
        $table->setStyle('compact');
        $table->setHeaders([
            '<fg=bright-white>#</>',
            '<fg=bright-white>表名</>',
            '<fg=bright-white>类型</>',
            '<fg=bright-white>类名</>',
        ]);

        $rows = [];
        foreach ($files as $index => $file) {
            $rows[] = [
                '<fg=' . $color . '>' . ($index + 1) . '</>',
                '<fg=' . $color . '>' . $file['table'] . '</>',
                '<fg=' . $color . '>' . $file['type'] . '</>',
                '<fg=' . $color . '>' . $file['class'] . '</>',
            ];
        }

        $table->setRows($rows);
        $table->render();
    }

    protected function getArguments()
    {
        return [
            ['tableName', InputArgument::OPTIONAL, '要删除的表名（可选，不填则删除所有）']
        ];
    }
}