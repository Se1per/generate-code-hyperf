<?php

declare(strict_types=1);

// namespace App\Base\src;
namespace Japool\Genconsole\Logger;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use Monolog\Logger;

/**
 * Logger 工厂类
 * 负责创建和缓存 Logger 实例
 * 
 * 使用示例：
 * $logger = $loggerFactory->get('api', [
 *     'level' => Logger::INFO,
 *     'max_files' => 30,
 *     'dir' => BASE_PATH . '/runtime/logs',
 *     'use_json' => false,
 *     'extra' => ['app' => 'myapp'],
 * ]);
 * $logger->info('消息', ['data' => 'value']);
 * 
 * 配置文件：config/autoload/generator.php 的 logger 分组
 */
class LoggerFactory
{
    protected array $instances = [];
    protected array $configured = [];

    public function __construct(
        protected ContainerInterface $container,
        protected ConfigInterface $config
    ) {}

    /**
     * 获取 Logger 实例
     * 
     * @param string $name Logger 名称（也是日志文件名）
     * @param array $options 配置选项：
     *   - level: 日志级别 (DEBUG|INFO|WARNING|ERROR)
     *   - max_files: 保留天数
     *   - dir: 日志目录
     *   - use_json: 是否使用 JSON 格式
     *   - extra: 额外字段
     * @return Logger
     * @throws \RuntimeException 当 logger 配置不存在且未提供 options 时
     * 
     * 配置优先级：
     * 1. 运行时 $options 参数
     * 2. generator.php 中的 logger.$name 配置
     * 3. LogMonMain 的默认配置
     */
    public function get(string $name = 'default', array $options = []): Logger
    {
        $cacheKey = $this->getCacheKey($name, $options);

        if (isset($this->instances[$cacheKey])) {
            return $this->instances[$cacheKey];
        }

        // 获取配置文件中的配置
        $configFromFile = $this->config->get('generator.logger.' . $name, []);
        
        // ⚠️ 验证：如果配置不存在且没有提供运行时选项，抛出错误
        if (empty($configFromFile) && empty($options)) {
            $availableLoggers = $this->getAvailableLoggers();
            throw new \RuntimeException(sprintf(
                "Logger 配置 '%s' 不存在，请先在 config/autoload/generator.php 的 'logger' 配置中定义。\n" .
                "可用的 Logger 配置: [%s]\n" .
                "或者在调用时提供完整的配置参数。",
                $name,
                implode(', ', $availableLoggers)
            ));
        }
 
        $config = array_merge($configFromFile, $options);

        $logger = new Logger($name);

        try {
            $logMonMain = $this->container->get(LogMonMain::class);
            $logMonMain->configureLogger($logger, $config);
            $this->configured[$cacheKey] = true;
        } catch (\Throwable $e) {
            error_log(sprintf(
                "[LoggerFactory] 配置失败: %s in %s:%d",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
            // 如果是配置验证失败，继续向上抛出
            if ($e instanceof \RuntimeException) {
                throw $e;
            }
        }

        return $this->instances[$cacheKey] = $logger;
    }

    /**
     * 生成缓存键
     */
    protected function getCacheKey(string $name, array $options): string
    {
        return empty($options) ? $name : $name . '_' . md5(serialize($options));
    }

    /**
     * 清理缓存的 Logger 实例
     * 
     * @param string|null $name 指定名称或 null 清理全部
     */
    public function clear(?string $name = null): void
    {
        if ($name === null) {
            $this->instances = [];
            $this->configured = [];
            return;
        }

        foreach (array_keys($this->instances) as $key) {
            if (str_starts_with($key, $name)) {
                unset($this->instances[$key], $this->configured[$key]);
            }
        }
    }

    /**
     * 检查 Logger 是否已配置
     */
    public function isConfigured(string $name, array $options = []): bool
    {
        return isset($this->configured[$this->getCacheKey($name, $options)]);
    }

    /**
     * 检查配置文件中是否存在指定的 Logger 配置
     */
    public function hasLoggerConfig(string $name): bool
    {
        return !empty($this->config->get('generator.logger.' . $name));
    }

    /**
     * 获取所有已缓存的 Logger 名称
     */
    public function getCachedLoggers(): array
    {
        return array_keys($this->instances);
    }

    /**
     * 获取所有可用的 Logger 配置
     */
    public function getAvailableLoggers(): array
    {
        return array_keys($this->config->get('generator.logger', []));
    }
}