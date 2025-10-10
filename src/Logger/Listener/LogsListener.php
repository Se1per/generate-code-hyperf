<?php

namespace Japool\Genconsole\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Japool\Genconsole\Event\LogsEvent;
use Hyperf\Event\Annotation\Listener;
use Japool\Genconsole\Logger\LoggerFactory as SrcLoggerFactory;
use Hyperf\Context\Context;
use Psr\Container\ContainerInterface;

#[Listener]
class LogsListener implements ListenerInterface
{
    private $loggerFactory;

    //$this->eventDispatcher->dispatch(new LogsEvent('business','error','name',['hahaha']));
    public function __construct(ContainerInterface $container)
    {
        $this->loggerFactory = $container->get(SrcLoggerFactory::class);
    }

    public function listen(): array
    {
        return [
            LogsEvent::class,
        ];
    }

    /**
     * 处理日志事件
     * @param LogsEvent $event
     */
    public function process(object $event): void
    {
        if (!$event instanceof LogsEvent) {
            return;
        }

        // 获取对应的 logger
        $logger = $this->loggerFactory->get($event->logger);

        // 构建统一的日志上下文
        $context = $this->buildLogContext($event);

        // 格式化日志消息
        $message = $this->formatLogMessage($event);

        // 根据状态记录日志
        switch ($event->status) {
            case 'info':
                $logger->info($message, $context);
                break;
            case 'warning':
                $logger->warning($message, $context);
                break;
            case 'error':
                $logger->error($message, $context);
                break;
            case 'debug':
                $logger->debug($message, $context);
                break;
            default:
                $logger->info($message, $context);
        }
    }

    /**
     * 构建统一的日志上下文 - 借鉴 DbSlowQueryExecutedListener 的结构
     */
    private function buildLogContext(LogsEvent $event): array
    {
        $context = [
            'title' => $event->title,
            'status' => $event->status,
            'timestamp' => date('Y-m-d H:i:s'),
            'request' => $this->getRequestInfo(),
            'call_chain' => $this->extractCallChain(),
        ];

        // 合并用户自定义的日志数据
        if (is_array($event->requestLog)) {
            $context['data'] = $event->requestLog;
        } else {
            $context['data'] = ['content' => $event->requestLog];
        }

        return $context;
    }

    /**
     * 格式化日志消息
     */
    private function formatLogMessage(LogsEvent $event): string
    {
        // $statusEmoji = $this->getStatusEmoji($event->status);
        return sprintf(
            '[%s] %s',
            strtoupper($event->status),
            $event->title
        );
    }

    /**
     * 获取状态对应的标识符
     */
    // private function getStatusEmoji(string $status): string
    // {
    //     $map = [
    //         'info' => '📝',
    //         'warning' => '⚠️',
    //         'error' => '❌',
    //         'debug' => '🔍',
    //         'success' => '✅',
    //     ];
    //     return $map[$status] ?? '📋';
    // }

    /**
     * 提取调用链 - 复用 DbSlowQueryExecutedListener 的逻辑
     */
    private function extractCallChain(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
        $chain = [];

        foreach ($trace as $item) {
            if (!isset($item['class'])) continue;
            $class = $item['class'];

            // 跳过框架和监听器类
            if (
                strpos($class, 'Hyperf\\') === 0 ||
                strpos($class, '\\Listener\\') !== false
            ) continue;

            if (strpos($class, 'Controller') !== false && !isset($chain['controller'])) {
                $chain['controller'] = [
                    'class' => $class,
                    'method' => $item['function'] ?? null,
                    'file' => $item['file'] ?? null,
                    'line' => $item['line'] ?? null,
                ];
            }
            if (strpos($class, 'Service') !== false && !isset($chain['service'])) {
                $chain['service'] = [
                    'class' => $class,
                    'method' => $item['function'] ?? null,
                ];
            }
            if (strpos($class, 'Repository') !== false && !isset($chain['repository'])) {
                $chain['repository'] = [
                    'class' => $class,
                    'method' => $item['function'] ?? null,
                ];
            }
        }

        return $chain;
    }

    /**
     * 获取请求信息 - 复用 DbSlowQueryExecutedListener 的逻辑
     */
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
                'user_agent' => $requestLog['server']['http_user_agent'] ?? null,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
