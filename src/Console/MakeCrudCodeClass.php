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
use function Hyperf\Support\env;

#[Command]
class MakeCrudCodeClass extends HyperfCommand
{
    use AutoCodeHelp;

    protected ContainerInterface $container;

    #[Value('generator')]
    protected $config;

    /**
     * 生成器配置列表
     */
    protected array $generators = [
        ['controller', 'generate:crud-controller', 'Controller', '控制器层', true],
        ['manager', 'generate:crud-manager', 'Manager', '业务层', false],
        ['model', 'generate:crud-model', '', '模型层', false],
        ['request', 'generate:crud-request', 'Request', '验证层', false],
        ['service', 'generate:crud-service', 'Service', '服务层', false],
        ['repository', 'generate:crud-repository', 'Repository', '数据层', false],
    ];

    /**
     * 生成统计
     */
    protected array $statistics = [
        'generated' => [],
        'skipped' => [],
        'total_tables' => 0,
        'total_generated' => 0,
        'total_skipped' => 0,
    ];

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
        $startTime = microtime(true);
        $argument = $this->input->getArgument('tableName') ?? '';
        
        $this->showWelcomeBanner($argument);
        
        $tables = $this->getAllTables();
        $this->statistics['total_tables'] = count($tables);

        if (empty($tables)) {
            $this->error('❌ 未找到任何数据库表');
            return;
        }

        $this->line('');
        $processedTables = 0;

        foreach ($tables as $tableName) {
            if ($argument && $tableName !== $argument) {
                continue;
            }

            $camelTableName = $this->camelCase($tableName);
            
            if ($this->keyWordsBlackList($camelTableName)) {
                $this->warn("⚠️  跳过黑名单表: {$tableName}");
                continue;
            }

            $processedTables++;
            $this->showTableHeader($tableName, $processedTables, count($tables));
            $this->generateAllFiles($camelTableName);
            $this->generateTestIfNeeded($camelTableName);
            $this->line('');
        }

        $this->showSummary($startTime);
    }

    protected function showWelcomeBanner(?string $tableName): void
    {
        $this->line('');
        $this->line('<fg=cyan>╔═══════════════════════════════════════════════════════════════╗</>');
        $this->line('<fg=cyan>║</> <fg=bright-white;options=bold>         🚀 Hyperf CRUD 代码生成器 v2.0 🚀              </><fg=cyan>║</>');
        $this->line('<fg=cyan>╚═══════════════════════════════════════════════════════════════╝</>');
        $this->line('');
        
        if ($tableName) {
            $this->info("📋 目标表: <fg=bright-white;options=bold>{$tableName}</>");
        } else {
            $this->info("📋 模式: <fg=bright-white;options=bold>批量生成所有表</>");
        }
        
        $dbDriver = env('DB_DRIVER', 'mysql');
        $this->comment("🗄️  数据库: <fg=bright-white>{$dbDriver}</>");
    }

    protected function showTableHeader(string $tableName, int $current, int $total): void
    {
        $percentage = round(($current / $total) * 100);
        $progressBar = $this->createProgressBar($percentage);
        
        $this->line("<fg=bright-blue>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>");
        $this->line("<fg=bright-white;options=bold>📦 [{$current}/{$total}] 处理表: {$tableName}</> {$progressBar} <fg=yellow>{$percentage}%</>");
        $this->line("<fg=bright-blue>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>");
    }

    protected function createProgressBar(int $percentage): string
    {
        $filled = (int) ($percentage / 5);
        $empty = 20 - $filled;
        
        return '<fg=green>' . str_repeat('█', $filled) . '</>' . 
               '<fg=gray>' . str_repeat('░', $empty) . '</>';
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
     * 生成所有类型的文件
     */
    protected function generateAllFiles(string $tableName): void
    {
        foreach ($this->generators as $generator) {
            [$configKey, $command, $suffix, $description, $needsApp] = $generator;
            
            $fileInfo = [
                'table' => $tableName,
                'type' => $description,
                'class' => $tableName . $suffix,
            ];
            
            // 检查调用前文件是否存在
            $existsBefore = $this->checkFileExists($configKey, $tableName, $suffix, $needsApp);
            
            // 调用子命令生成
            $this->call($command, ['name' => $tableName]);
            
            // 检查调用后文件是否存在
            $existsAfter = $this->checkFileExists($configKey, $tableName, $suffix, $needsApp);
            
            // 判断文件是新生成的还是已存在的
            if (!$existsBefore && $existsAfter) {
                // 之前不存在，现在存在 = 新生成
                $this->statistics['generated'][] = $fileInfo;
                $this->statistics['total_generated']++;
                $this->line("   <fg=green>✓</> {$description}: <fg=bright-white>{$tableName}{$suffix}</>");
            } else if ($existsBefore) {
                // 之前就存在 = 跳过
                $this->statistics['skipped'][] = $fileInfo;
                $this->statistics['total_skipped']++;
                $this->line("   <fg=yellow>⊘</> {$description}: <fg=gray>{$tableName}{$suffix} (已存在)</>");
            }
        }
    }

    /**
     * 检查文件是否存在
     */
    protected function checkFileExists(
        string $configKey, 
        string $tableName, 
        string $suffix, 
        bool $needsApp
    ): bool {
        $namespace = $this->config['general'][$configKey];
        
        if ($needsApp) {
            $namespace .= '\\' . $this->config['general']['app'];
        }
        
        $className = $namespace . '\\' . $tableName . $suffix;
        
        // 将命名空间转换为文件路径
        $filePath = $this->namespaceToFilePath($className);
        
        return file_exists($filePath);
    }
    
    /**
     * 将命名空间转换为文件路径
     */
    protected function namespaceToFilePath(string $namespace): string
    {
        $relativePath = str_replace('\\', '/', $namespace);
        $relativePath = str_replace('App/', 'app/', $relativePath);
        return BASE_PATH . '/' . $relativePath . '.php';
    }

    /**
     * 如果需要，生成测试文件
     */
    protected function generateTestIfNeeded(string $tableName): void
    {
        if (!$this->isTestIngExtensionInstalled()) {
            return;
        }

        $testClassName = 'App\\Test\\' . $tableName . 'ControllerTest';
        
        $fileInfo = [
            'table' => $tableName,
            'type' => '测试实例',
            'class' => $tableName . 'ControllerTest',
        ];
        
        // 检查调用前文件是否存在
        $testFilePath = $this->namespaceToFilePath($testClassName);
        $existsBefore = file_exists($testFilePath);
        
        // 调用子命令生成
        $this->call('generate:generateTest', ['name' => $tableName]);
        
        // 检查调用后文件是否存在
        $existsAfter = file_exists($testFilePath);
        
        // 判断文件是新生成的还是已存在的
        if (!$existsBefore && $existsAfter) {
            // 之前不存在，现在存在 = 新生成
            $this->statistics['generated'][] = $fileInfo;
            $this->statistics['total_generated']++;
            $this->line("   <fg=green>✓</> 测试实例: <fg=bright-white>{$tableName}ControllerTest</>");
        } else if ($existsBefore) {
            // 之前就存在 = 跳过
            $this->statistics['skipped'][] = $fileInfo;
            $this->statistics['total_skipped']++;
            $this->line("   <fg=yellow>⊘</> 测试实例: <fg=gray>{$tableName}ControllerTest (已存在)</>");
        }
    }

    /**
     * 显示生成总结
     */
    protected function showSummary(float $startTime): void
    {
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->line('');
        $this->line('<fg=cyan>╔═══════════════════════════════════════════════════════════════╗</>');
        $this->line('<fg=cyan>║</> <fg=bright-white;options=bold>                    📊 生成总结                          </><fg=cyan>║</>');
        $this->line('<fg=cyan>╚═══════════════════════════════════════════════════════════════╝</>');
        $this->line('');

        // 创建统计表格
        $table = new Table($this->output);
        $table->setStyle('box-double');
        $table->setHeaders([
            '<fg=bright-white;options=bold>统计项</>',
            '<fg=bright-white;options=bold>数量</>',
        ]);

        $totalFiles = $this->statistics['total_generated'] + $this->statistics['total_skipped'];

        $table->setRows([
            ['<fg=cyan>处理表数</>', '<fg=bright-white>' . $this->statistics['total_tables'] . '</>'],
            ['<fg=green>新生成文件</>', '<fg=green;options=bold>' . $this->statistics['total_generated'] . '</>'],
            ['<fg=yellow>跳过文件</>', '<fg=yellow>' . $this->statistics['total_skipped'] . '</>'],
            new TableSeparator(),
            ['<fg=bright-white;options=bold>总文件数</>', '<fg=bright-white;options=bold>' . $totalFiles . '</>'],
            ['<fg=bright-white;options=bold>耗时</>', '<fg=bright-white;options=bold>' . $duration . ' 秒</>'],
        ]);

        $table->render();
        
        // 如果有生成的文件，显示详细列表
        if (!empty($this->statistics['generated'])) {
            $this->line('');
            $this->line('<fg=green;options=bold>✨ 新生成的文件：</>');
            $this->showFileList($this->statistics['generated'], 'green');
        }

        // 如果有跳过的文件
        if (!empty($this->statistics['skipped'])) {
            $this->line('');
            $this->line('<fg=yellow;options=bold>⊘ 跳过的文件（已存在）：</>');
            $this->showFileList($this->statistics['skipped'], 'yellow');
        }

        $this->line('');
        
        if ($this->statistics['total_generated'] > 0) {
            $this->line('<fg=green;options=bold>🎉 代码生成完成！成功生成 ' . $this->statistics['total_generated'] . ' 个文件</>');
        } else {
            $this->line('<fg=yellow;options=bold>ℹ️  没有新文件生成，所有文件都已存在</>');
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
            ['tableName', InputArgument::OPTIONAL, '表名']
        ];
    }
}