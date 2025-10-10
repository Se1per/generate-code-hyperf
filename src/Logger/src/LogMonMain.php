<?php

declare(strict_types=1);

namespace Japool\Genconsole\Logger\src;

use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Hyperf\Context\Context;
use Psr\Log\LoggerInterface;
use Monolog\LogRecord;

/**
 * 日志管理类
 */
class LogMonMain
{
    private string $dir;
    private int $maxFiles;
    private int $minLevel;
    private bool $useJson;
    private array $loggerCache = [];
    private array $configuredLoggers = [];

    public function __construct(
        string $dir = BASE_PATH . '/runtime/logs',
        int $maxFiles = 30,
        int $minLevel = Logger::INFO,
        bool $useJson = false
    ) {
        $this->dir = rtrim($dir, '/');
        $this->maxFiles = max(1, $maxFiles);
        $this->minLevel = $minLevel;
        $this->useJson = $useJson;
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new \RuntimeException("无法创建目录：{$dir}");
            }
        }

        if (!is_writable($dir)) {
            throw new \RuntimeException("目录不可写：{$dir}");
        }
    }

    /**
     * 配置日志处理器 - 简化版，直接写入
     */
    private function configureHandler(
        Logger $logger,
        string $dir,
        int $maxFiles,
        int $minLevel,
        bool $useJson
    ): void {
        $this->ensureDir($dir);

        $filename = sprintf('%s/%s.log', $dir, $logger->getName());
        
        // 使用 RotatingFileHandler，自动按天轮转
        $handler = new RotatingFileHandler(
            $filename,
            $maxFiles,
            $minLevel,
            true,    // bubble
            0644,    // 文件权限
            true     // useLocking - 使用文件锁
        );

        // 选择格式化器
        if ($useJson) {
            $formatter = new JsonFormatter();
        } else {
            $dateFormat = "Y-m-d H:i:s.u";
            $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
            $formatter = new LineFormatter($output, $dateFormat, true, true);
        }
        $handler->setFormatter($formatter);

        $logger->pushHandler($handler);
    }

    private function formatMemoryUsage(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }

    private function addProcessor(Logger $logger): void
    {
        $logger->pushProcessor(function (LogRecord $record) {
            try {
                $req = Context::get('request_info') ?? [];
                $requestId = $req['request_id'] ?? str_replace('.', '-', uniqid('req_', true));
                $coroutineId = \Hyperf\Coroutine\Coroutine::id();

                $extra = [
                    'request_id' => $requestId,
                    'coroutine_id' => $coroutineId > 0 ? $coroutineId : 'non-coroutine',
                    'memory_usage' => $this->formatMemoryUsage(memory_get_usage(true)),
                    'peak_memory' => $this->formatMemoryUsage(memory_get_peak_usage(true)),
                ];

                if (!empty($req)) {
                    $extra['request_info'] = $req;
                }

                $record->extra = array_merge($record->extra, $extra);
            } catch (\Throwable $e) {
                error_log("Logger processor error: " . $e->getMessage());
            }

            return $record;
        });
    }

    public function configureLogger(Logger $logger, array $config = []): void
    {
        $loggerHash = spl_object_hash($logger);
        if (isset($this->configuredLoggers[$loggerHash])) {
            return;
        }

        $dir = $config['dir'] ?? $this->dir;
        $maxFiles = $config['max_files'] ?? $this->maxFiles;
        $minLevel = $config['level'] ?? $this->minLevel;
        $useJson = $config['use_json'] ?? $this->useJson;

        try {
            $this->configureHandler($logger, $dir, $maxFiles, $minLevel, $useJson);
            $this->addProcessor($logger);

            if (!empty($config['extra']) && is_array($config['extra'])) {
                $logger->pushProcessor(function (LogRecord $record) use ($config) {
                    $record->extra = array_merge($record->extra, $config['extra']);
                    return $record;
                });
            }

            $this->configuredLoggers[$loggerHash] = true;
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Logger 配置失败: " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    private function getLogger(string $channel): LoggerInterface
    {
        if (isset($this->loggerCache[$channel])) {
            return $this->loggerCache[$channel];
        }

        $logger = new Logger($channel);
        
        try {
            $this->configureHandler($logger, $this->dir, $this->maxFiles, $this->minLevel, $this->useJson);
            $this->addProcessor($logger);
            $this->configuredLoggers[spl_object_hash($logger)] = true;
        } catch (\Throwable $e) {
            error_log(sprintf(
                "Failed to configure logger for channel '%s': %s",
                $channel,
                $e->getMessage()
            ));
        }

        return $this->loggerCache[$channel] = $logger;
    }

    private function norm($context): array
    {
        if ($context === null) {
            return [];
        }
        
        if (is_array($context)) {
            return $context;
        }
        
        if (is_scalar($context) || (is_object($context) && method_exists($context, '__toString'))) {
            return ['data' => $context];
        }
        
        return ['data' => var_export($context, true)];
    }

    public function debug(string $channel, $msg, $context = null): bool
    {
        try {
            $this->getLogger($channel)->debug((string) $msg, $this->norm($context));
            return true;
        } catch (\Throwable $e) {
            error_log("Log failed: " . $e->getMessage());
            return false;
        }
    }

    public function info(string $channel, $msg, $context = null): bool
    {
        try {
            $this->getLogger($channel)->info((string) $msg, $this->norm($context));
            return true;
        } catch (\Throwable $e) {
            error_log("Log failed: " . $e->getMessage());
            return false;
        }
    }

    public function notice(string $channel, $msg, $context = null): bool
    {
        try {
            $this->getLogger($channel)->notice((string) $msg, $this->norm($context));
            return true;
        } catch (\Throwable $e) {
            error_log("Log failed: " . $e->getMessage());
            return false;
        }
    }

    public function warning(string $channel, $msg, $context = null): bool
    {
        try {
            $this->getLogger($channel)->warning((string) $msg, $this->norm($context));
            return true;
        } catch (\Throwable $e) {
            error_log("Log failed: " . $e->getMessage());
            return false;
        }
    }

    public function error(string $channel, $msg, $context = null): bool
    {
        try {
            $this->getLogger($channel)->error((string) $msg, $this->norm($context));
            return true;
        } catch (\Throwable $e) {
            error_log("Log failed: " . $e->getMessage());
            return false;
        }
    }

    public function critical(string $channel, $msg, $context = null): bool
    {
        try {
            $this->getLogger($channel)->critical((string) $msg, $this->norm($context));
            return true;
        } catch (\Throwable $e) {
            error_log("Log failed: " . $e->getMessage());
            return false;
        }
    }

    public function alert(string $channel, $msg, $context = null): bool
    {
        try {
            $this->getLogger($channel)->alert((string) $msg, $this->norm($context));
            return true;
        } catch (\Throwable $e) {
            error_log("Log failed: " . $e->getMessage());
            return false;
        }
    }

    public function emergency(string $channel, $msg, $context = null): bool
    {
        try {
            $this->getLogger($channel)->emergency((string) $msg, $this->norm($context));
            return true;
        } catch (\Throwable $e) {
            error_log("Log failed: " . $e->getMessage());
            return false;
        }
    }

    public function clearCache(?string $channel = null): void
    {
        if ($channel === null) {
            $this->loggerCache = [];
            $this->configuredLoggers = [];
        } else {
            if (isset($this->loggerCache[$channel])) {
                unset($this->loggerCache[$channel]);
            }
        }
    }

    public function getCachedChannels(): array
    {
        return array_keys($this->loggerCache);
    }
}