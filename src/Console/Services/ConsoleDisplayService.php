<?php

namespace Japool\Genconsole\Console\Services;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * 控制台显示服务
 * 职责：处理所有UI展示相关逻辑
 */
class ConsoleDisplayService
{
    public function __construct(
        private OutputInterface $output,
        private SymfonyStyle $io
    ) {
    }

    /**
     * 显示欢迎横幅
     */
    public function showWelcomeBanner(
        string $title,
        ?string $tableName,
        string $dbConnection,
        string $dbDriver,
        string $dbDatabase,
        string $color = 'cyan'
    ): void {
        $this->io->newLine();
        $this->io->writeln("<fg={$color}>╔═══════════════════════════════════════════════════════════════╗</>");
        $this->io->writeln("<fg={$color}>║</> <fg=bright-white;options=bold>         {$title}              </><fg={$color}>║</>");
        $this->io->writeln("<fg={$color}>╚═══════════════════════════════════════════════════════════════╝</>");
        $this->io->newLine();
        
        if ($tableName) {
            $this->io->info("📋 目标表: <fg=bright-white;options=bold>{$tableName}</>");
        } else {
            $this->io->info("📋 模式: <fg=bright-white;options=bold>批量处理所有表</>");
        }
        
        $this->io->comment("🗄️  数据库连接: <fg=bright-white>{$dbConnection}</>");
        $this->io->comment("🗄️  数据库类型: <fg=bright-white>{$dbDriver}</>");
        $this->io->comment("🗄️  数据库名称: <fg=bright-white>{$dbDatabase}</>");
    }

    /**
     * 显示表处理头部
     */
    public function showTableHeader(
        string $tableName, 
        int $current, 
        int $total,
        string $color = 'bright-blue',
        string $icon = '📦'
    ): void {
        $percentage = round(($current / $total) * 100);
        $progressBar = $this->createProgressBar($percentage, $color === 'red' ? 'red' : 'green');
        
        $this->io->writeln("<fg={$color}>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>");
        $this->io->writeln("<fg=bright-white;options=bold>{$icon} [{$current}/{$total}] 处理表: {$tableName}</> {$progressBar} <fg=yellow>{$percentage}%</>");
        $this->io->writeln("<fg={$color}>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>");
    }

    /**
     * 创建进度条
     */
    public function createProgressBar(int $percentage, string $color = 'green'): string
    {
        $filled = (int) ($percentage / 5);
        $empty = 20 - $filled;
        
        return "<fg={$color}>" . str_repeat('█', $filled) . '</>' . 
               '<fg=gray>' . str_repeat('░', $empty) . '</>';
    }

    /**
     * 显示生成总结
     */
    public function showGenerateSummary(array $statistics, float $duration): void
    {
        $this->io->newLine();
        $this->io->writeln('<fg=cyan>╔═══════════════════════════════════════════════════════════════╗</>');
        $this->io->writeln('<fg=cyan>║</> <fg=bright-white;options=bold>                    📊 生成总结                          </><fg=cyan>║</>');
        $this->io->writeln('<fg=cyan>╚═══════════════════════════════════════════════════════════════╝</>');
        $this->io->newLine();

        $table = new Table($this->output);
        $table->setStyle('box-double');
        $table->setHeaders([
            '<fg=bright-white;options=bold>统计项</>',
            '<fg=bright-white;options=bold>数量</>',
        ]);

        $table->setRows([
            ['<fg=cyan>处理表数</>', '<fg=bright-white>' . $statistics['total_tables'] . '</>'],
            ['<fg=green>新生成文件</>', '<fg=green;options=bold>' . $statistics['total_generated'] . '</>'],
            ['<fg=yellow>跳过文件</>', '<fg=yellow>' . $statistics['total_skipped'] . '</>'],
            new TableSeparator(),
            ['<fg=bright-white;options=bold>总文件数</>', '<fg=bright-white;options=bold>' . $statistics['total_files'] . '</>'],
            ['<fg=bright-white;options=bold>耗时</>', '<fg=bright-white;options=bold>' . $duration . ' 秒</>'],
        ]);

        $table->render();
        
        if (!empty($statistics['generated'])) {
            $this->io->newLine();
            $this->io->writeln('<fg=green;options=bold>✨ 新生成的文件：</>');
            $this->showFileList($statistics['generated'], 'green');
        }

        if (!empty($statistics['skipped'])) {
            $this->io->newLine();
            $this->io->writeln('<fg=yellow;options=bold>⊘ 跳过的文件（已存在）：</>');
            $this->showFileList($statistics['skipped'], 'yellow');
        }

        $this->io->newLine();
        
        if ($statistics['total_generated'] > 0) {
            $this->io->writeln('<fg=green;options=bold>🎉 代码生成完成！成功生成 ' . $statistics['total_generated'] . ' 个文件</>');
        } else {
            $this->io->writeln('<fg=yellow;options=bold>ℹ️  没有新文件生成，所有文件都已存在</>');
        }
        
        $this->io->newLine();
    }

    /**
     * 显示删除总结
     */
    public function showDeleteSummary(array $statistics, float $duration): void
    {
        $this->io->newLine();
        $this->io->writeln('<fg=red>╔═══════════════════════════════════════════════════════════════╗</>');
        $this->io->writeln('<fg=red>║</> <fg=bright-white;options=bold>                    📊 删除总结                          </><fg=red>║</>');
        $this->io->writeln('<fg=red>╚═══════════════════════════════════════════════════════════════╝</>');
        $this->io->newLine();

        $table = new Table($this->output);
        $table->setStyle('box-double');
        $table->setHeaders([
            '<fg=bright-white;options=bold>统计项</>',
            '<fg=bright-white;options=bold>数量</>',
        ]);

        $table->setRows([
            ['<fg=cyan>处理表数</>', '<fg=bright-white>' . $statistics['total_tables'] . '</>'],
            ['<fg=green>成功删除</>', '<fg=green;options=bold>' . $statistics['total_deleted'] . '</>'],
            ['<fg=red>失败/不存在</>', '<fg=red>' . $statistics['total_notFound'] . '</>'],
            new TableSeparator(),
            ['<fg=bright-white;options=bold>总文件数</>', '<fg=bright-white;options=bold>' . $statistics['total_files'] . '</>'],
            ['<fg=bright-white;options=bold>耗时</>', '<fg=bright-white;options=bold>' . $duration . ' 秒</>'],
        ]);

        $table->render();
        
        if (!empty($statistics['deleted'])) {
            $this->io->newLine();
            $this->io->writeln('<fg=green;options=bold>✓ 成功删除的文件：</>');
            $this->showFileList($statistics['deleted'], 'green');
        }

        if (!empty($statistics['notFound'])) {
            $this->io->newLine();
            $this->io->writeln('<fg=red;options=bold>✗ 删除失败或不存在的文件：</>');
            $this->showFileList($statistics['notFound'], 'red');
        }

        $this->io->newLine();
        
        if ($statistics['total_deleted'] > 0) {
            $this->io->writeln('<fg=green;options=bold>🎉 删除完成！成功删除 ' . $statistics['total_deleted'] . ' 个文件</>');
        } else {
            $this->io->writeln('<fg=yellow;options=bold>ℹ️  没有文件被删除</>');
        }
        
        $this->io->newLine();
    }

    /**
     * 显示文件列表
     */
    public function showFileList(array $files, string $color): void
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
                "<fg={$color}>" . ($index + 1) . '</>',
                "<fg={$color}>" . $file['table'] . '</>',
                "<fg={$color}>" . $file['type'] . '</>',
                "<fg={$color}>" . $file['class'] . '</>',
            ];
        }

        $table->setRows($rows);
        $table->render();
    }

    /**
     * 显示要删除的文件列表
     */
    public function showFilesToDelete(array $files): void
    {
        $this->io->writeln('<fg=red;options=bold>⚠️  以下文件将被删除：</>');
        $this->io->newLine();

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
        
        $this->io->newLine();
        $this->io->warning("共 " . count($files) . " 个文件将被删除");
        $this->io->newLine();
    }

    /**
     * 显示操作消息
     */
    public function showOperationMessage(string $type, string $message, string $detail = ''): void
    {
        $icons = [
            'success' => '✓',
            'warning' => '⊘',
            'error' => '✗',
            'info' => 'ℹ',
        ];
        
        $colors = [
            'success' => 'green',
            'warning' => 'yellow',
            'error' => 'red',
            'info' => 'blue',
        ];
        
        $icon = $icons[$type] ?? '•';
        $color = $colors[$type] ?? 'white';
        
        $output = "   <fg={$color}>{$icon}</> {$message}";
        if ($detail) {
            $output .= ": <fg=gray>{$detail}</>";
        }
        
        $this->io->writeln($output);
    }
}

