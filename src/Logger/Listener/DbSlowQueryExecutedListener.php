<?php

declare(strict_types=1);

namespace Japool\Genconsole\Listener;

use Japool\Genconsole\Logger\LoggerFactory as SrcLoggerFactory;
use Hyperf\Collection\Arr;
use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Context\Context;
use Psr\Container\ContainerInterface;
use App\Event\SlowExecutionEvent;

#[Listener]
class DbSlowQueryExecutedListener implements ListenerInterface
{
    private $logger;
    private $slowLogger;
    private float $slowQueryThreshold = 100.0;

    public function __construct(ContainerInterface $container)
    {
        $loggerFactory = $container->get(SrcLoggerFactory::class);
        $this->logger = $loggerFactory->get('slow-execution-auto');
        $this->slowLogger = $loggerFactory->get('slow-execution');
        
        $config = $container->get(\Hyperf\Contract\ConfigInterface::class);
        $this->slowQueryThreshold = (float)($config->get('generator.slow_query_threshold', 100.0));
    }

    public function listen(): array
    {
        return [
            QueryExecuted::class,
            SlowExecutionEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof QueryExecuted) {
            $this->handleQueryExecuted($event);
        } elseif ($event instanceof SlowExecutionEvent) {
            $this->handleSlowExecution($event);
        }
    }

    /**
     * 处理数据库查询
     */
    private function handleQueryExecuted(QueryExecuted $event): void
    {
        $sql = $this->formatSql($event->sql, $event->bindings);
        $time = $event->time;
        
        // 记录所有 SQL
        $this->logger->info(sprintf('[%s ms] %s', $time, $sql));
        
        // 收集到切面
        $collectorKey = Context::get('current_sql_collector');
        if ($collectorKey) {
            $collector = Context::get($collectorKey, []);
            $collector[] = [
                'sql' => $sql,
                'raw_sql' => $event->sql,
                'bindings' => $event->bindings,
                'time' => $time,
            ];
            Context::set($collectorKey, $collector);
        }
        
        // 检测慢 SQL - 统一保存
        if ($time >= $this->slowQueryThreshold) {
            $this->saveSlowLog($event->sql, $time, [
                ['sql' => $sql, 'raw_sql' => $event->sql, 'time' => $time, 'bindings' => $event->bindings]
            ]);
        }
    }

    /**
     * 处理切面慢执行
     */
    private function handleSlowExecution(SlowExecutionEvent $event): void
    {
        // 统一保存
        $this->saveSlowLog($event->name, $event->time, $event->sqlList, $event->extra);
    }

    /**
     * 统一保存慢日志 - 唯一的日志保存逻辑
     */
    private function saveSlowLog(string $name, float $time, array $sqlList, array $extra = []): void
    {
        // 计算 SQL 统计
        $totalSqlTime = 0;
        $sqls = [];
        foreach ($sqlList as $sql) {
            $totalSqlTime += $sql['time'] ?? 0;
            $sqls[] = [
                'sql' => $sql['sql'] ?? '',
                'raw_sql' => $sql['raw_sql'] ?? $sql['sql'] ?? '',
                'time_ms' => round($sql['time'] ?? 0, 2),
                'bindings' => $sql['bindings'] ?? [],
            ];
        }

        // 统一的日志格式
        $context = [
            'name' => $name,
            'time_ms' => round($time, 2),
            'sql_count' => count($sqlList),
            'sql_total_time' => round($totalSqlTime, 2),
            'sql_list' => $sqls,
            'call_chain' => $this->extractCallChain(),
            'request' => $this->getRequestInfo(),
        ];

        // 合并额外信息
        $context = array_merge($context, $extra);

        $message = sprintf(
            '%s: %.2f ms (SQL: %d 条)',
            $name,
            $time,
            count($sqlList)
        );
 
        $this->slowLogger->warning($message, $context);
    }

    private function formatSql(string $sql, array $bindings): string
    {
        if (!Arr::isAssoc($bindings)) {
            $position = 0;
            foreach ($bindings as $value) {
                $position = strpos($sql, '?', $position);
                if ($position === false) break;
                $value = $this->formatValue($value);
                $sql = substr_replace($sql, $value, $position, 1);
                $position += strlen($value);
            }
        }
        return $sql;
    }
    
    private function formatValue($value): string
    {
        if (is_null($value)) return 'NULL';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_numeric($value)) return (string)$value;
        return "'" . addslashes((string)$value) . "'";
    }

    private function extractCallChain(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
        $chain = [];

        foreach ($trace as $item) {
            if (!isset($item['class'])) continue;
            $class = $item['class'];
            
            if (strpos($class, 'Hyperf\\') === 0 || 
                strpos($class, '\\Listener\\') !== false) continue;

            if (strpos($class, 'Controller') !== false && !isset($chain['controller'])) {
                $chain['controller'] = ['class' => $class, 'method' => $item['function'] ?? null];
            }
            if (strpos($class, 'Service') !== false && !isset($chain['service'])) {
                $chain['service'] = ['class' => $class, 'method' => $item['function'] ?? null];
            }
            if (strpos($class, 'Repository') !== false && !isset($chain['repository'])) {
                $chain['repository'] = ['class' => $class, 'method' => $item['function'] ?? null];
            }
        }

        return $chain;
    }

    private function getRequestInfo(): ?array
    {
        try {
            $requestLog = Context::get('request_log');
            if (!$requestLog) return null;
            
            return [
                'request_id' => $requestLog['request_id'] ?? null,
                'method' => $requestLog['server']['request_method'] ?? null,
                'uri' => $requestLog['server']['request_uri'] ?? null,
                'ip' => $requestLog['server']['remote_addr'] ?? null,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}