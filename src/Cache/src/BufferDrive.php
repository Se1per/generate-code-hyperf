<?php

namespace Japool\Genconsole\Cache\src;

use Hyperf\Redis\RedisFactory;
use Hyperf\Context\ApplicationContext;
use App\Base\src\LoggerFactory;
use Hyperf\Contract\ConfigInterface;

class BufferDrive
{
    private $drive;
    private $cacheKeys = 'CacheGroup:';
    private $dataKey = null;
    private $config = null;
    private $logger = null;
    private $globalConfig = null; // 全局配置

    /**
     * 获取全局配置
     */
    private function getGlobalConfig(): array
    {
        if ($this->globalConfig === null) {
            try {
                $container = ApplicationContext::getContainer();
                $config = $container->get(ConfigInterface::class);
                $this->globalConfig = $config->get('generator.cache', []);
            } catch (\Throwable $e) {
                $this->globalConfig = [];
            }
        }
        return $this->globalConfig;
    }
    
    /**
     * 获取配置项（支持 group 级别覆盖）
     */
    private function getConfigValue(string $key, $default = null)
    {
        $globalConfig = $this->getGlobalConfig();
        
        // 如果有 group，检查是否有 group 级别的配置
        if ($this->config->group && isset($globalConfig['groups'][$this->config->group][$key])) {
            return $globalConfig['groups'][$this->config->group][$key];
        }
        
        // 返回全局配置或默认值
        return $globalConfig[$key] ?? $default;
    }

    /**
     * 获取 Logger 实例
     */
    private function getLogger()
    {
        if ($this->logger === null) {
            try {
                $container = ApplicationContext::getContainer();
                $loggerFactory = $container->get(LoggerFactory::class);
                $this->logger = $loggerFactory->get('cache');
            } catch (\Throwable $e) {
                $this->logger = new class {
                    public function debug($message, $context = []) {}
                    public function info($message, $context = []) {}
                    public function error($message, $context = []) {}
                };
            }
        }
        return $this->logger;
    }
    
    /**
     * 检查是否允许缓存（黑白名单）
     */
    private function isAllowedToCache(): bool
    {
        $globalConfig = $this->getGlobalConfig();
        
        // 总开关关闭
        if (!($globalConfig['enable'] ?? true)) {
            return false;
        }
        
        $group = $this->config->group ?? '';
        $prefix = $this->config->prefix ?? '';
        
        // 组合键
        $fullKey = $group ? "{$group}.{$prefix}" : $prefix;
        
        // 白名单优先（如果配置了白名单，只允许白名单中的）
        $whitelist = $globalConfig['whitelist'] ?? [];
        if (!empty($whitelist)) {
            // 完全匹配或 group 匹配
            return in_array($fullKey, $whitelist) || in_array($group, $whitelist);
        }
        
        // 黑名单检查（白名单为空时才检查黑名单）
        $blacklist = $globalConfig['blacklist'] ?? [];
        if (!empty($blacklist)) {
            // 完全匹配或 group 匹配则不允许
            if (in_array($fullKey, $blacklist) || in_array($group, $blacklist)) {
                return false;
            }
        }
        
        // 默认允许
        return true;
    }

    /**
     * 获取cache
     */
    public function getCache($config, $arguments)
    {
        try {
            $this->setDrive($config);
            
            // 黑白名单检查
            if (!$this->isAllowedToCache()) {
                return null;
            }
            
            $cacheKey = $this->generateCacheKey($arguments);
            $data = $this->drive->get($cacheKey);
            
            if (!empty($data)) {
                $data = $this->decompress($data);
                return $data;
            }
            
            return null;
            
        } catch (\Throwable $e) {
            $this->getLogger()->error("Cache get error: " . $e->getMessage(), [
                'key' => $cacheKey ?? 'unknown'
            ]);
            return null;
        }
    }
    
    /**
     * 设置cache
     */
    public function setCache($config, $arguments, $result)
    {
        try {
            $this->setDrive($config);
            
            // 黑白名单检查
            if (!$this->isAllowedToCache()) {
                return false;
            }
            
            $cacheKey = $this->generateCacheKey($arguments);
            
            // 概率性检查并清理（使用配置）
            $this->probabilisticCleanup();
            
            // 压缩数据
            $compressed = $this->compress($result);
            $ttl = $this->getTtlWithJitter($config->ttl);
            
            // 使用 pipeline 批量执行
            $pipe = $this->drive->multi();
            $pipe->setex($cacheKey, $ttl, $compressed);
            
            // 添加到 group SET
            $groupSetKey = $this->getGroupSetKey();
            $pipe->sAdd($groupSetKey, $cacheKey);
            
            // SET 过期时间（使用配置）
            $setTtlExtra = $this->getConfigValue('set_ttl_extra', 3600);
            $setTtl = max($ttl + $setTtlExtra, 86400);
            $pipe->expire($groupSetKey, $setTtl);
            
            $results = $pipe->exec();
            
            return $results[0] ?? false;
            
        } catch (\Throwable $e) {
            $this->getLogger()->error("Cache set error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 生成缓存键
     */
    private function generateCacheKey($arguments): string
    {
        if (is_scalar($arguments)) {
            $key = $arguments;
        } else {
            $key = md5(json_encode($arguments, JSON_UNESCAPED_UNICODE));
        }
        
        return $this->dataKey . $key;
    }
    
    /**
     * 压缩数据（使用配置）
     */
    private function compress($data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        // 检查是否启用压缩
        $compressEnable = $this->getConfigValue('compress_enable', true);
        if (!$compressEnable) {
            return 'raw:' . $json;
        }
        
        // 使用配置的阈值
        $threshold = $this->getConfigValue('compress_threshold', 1024);
        
        if (strlen($json) > $threshold) {
            return 'gz:' . gzcompress($json, 6);
        }
        
        return 'raw:' . $json;
    }
    
    /**
     * 解压数据
     */
    private function decompress($data)
    {
        if (str_starts_with($data, 'gz:')) {
            $json = gzuncompress(substr($data, 3));
        } elseif (str_starts_with($data, 'raw:')) {
            $json = substr($data, 4);
        } else {
            // 兼容旧格式
            $json = $data;
        }
        
        return json_decode($json, true);
    }
    
    /**
     * TTL 加随机偏移（使用配置）
     */
    private function getTtlWithJitter(int $ttl): int
    {
        $jitterRatio = $this->getConfigValue('jitter_ratio', 0.1);
        $jitter = (int)($ttl * $jitterRatio);
        return $ttl + random_int(-$jitter, $jitter);
    }
    
    /**
     * 概率性清理（使用配置）
     */
    private function probabilisticCleanup()
    {
        $probability = $this->getConfigValue('cleanup_probability', 0.01);
        $rand = mt_rand(1, 10000) / 10000; // 0.0001 - 1.0
        
        if ($rand > $probability) {
            return;
        }
        
        try {
            $groupSetKey = $this->getGroupSetKey();
            $count = $this->drive->sCard($groupSetKey);
            
            $maxSize = $this->getConfigValue('max_cache_size', 10000);
            
            if ($count > $maxSize) {
                $this->cleanupCache($groupSetKey, $count);
            }
        } catch (\Throwable $e) {
            // 清理失败不影响业务
        }
    }
    
    /**
     * 清理缓存（使用配置）
     */
    private function cleanupCache($groupSetKey, $currentCount)
    {
        $cleanupRatio = $this->getConfigValue('cleanup_ratio', 0.1);
        $toDelete = (int)($currentCount * $cleanupRatio);
        
        $keys = $this->drive->sRandMember($groupSetKey, $toDelete);
        
        if (!$keys || !is_array($keys)) {
            return;
        }
        
        // 使用 pipeline 批量操作
        $pipe = $this->drive->multi();
        foreach ($keys as $key) {
            $pipe->del($key);
            $pipe->sRem($groupSetKey, $key);
        }
        $pipe->exec();
        
        $this->getLogger()->info("Cache cleaned", [
            'group' => $this->config->group ?? 'default',
            'deleted' => count($keys),
            'remaining' => $currentCount - count($keys)
        ]);
    }
    
    /**
     * 获取 Group SET 键名
     */
    private function getGroupSetKey(): string
    {
        if ($this->config->group) {
            return $this->cacheKeys . $this->config->group . ':set';
        } else {
            return $this->dataKey . 'set';
        }
    }
    
    /**
     * 删除缓存组
     */
    public function removeCache($config)
    {
        try {
            $this->setDrive($config);
            $groupSetKey = $this->getGroupSetKey();
            
            $keys = $this->drive->sMembers($groupSetKey);
            
            if (empty($keys)) {
                $this->getLogger()->info("No cache to remove", [
                    'group' => $config->group ?? 'none',
                    'prefix' => $config->prefix
                ]);
                return 0;
            }
            
            // 分批删除
            $batches = array_chunk($keys, 500);
            $totalDeleted = 0;
            
            foreach ($batches as $batch) {
                $pipe = $this->drive->multi();
                foreach ($batch as $key) {
                    $pipe->del($key);
                }
                $pipe->exec();
                
                $totalDeleted += count($batch);
            }
            
            // 删除 SET 本身
            $this->drive->del($groupSetKey);
            
            $this->getLogger()->info("Cache group removed", [
                'group' => $config->group ?? 'none',
                'prefix' => $config->prefix,
                'count' => $totalDeleted,
                'set_key' => $groupSetKey
            ]);
            
            return $totalDeleted;
            
        } catch (\Throwable $e) {
            $this->getLogger()->error("Cache remove error: " . $e->getMessage(), [
                'group' => $config->group ?? 'none',
                'prefix' => $config->prefix ?? 'none'
            ]);
            return 0;
        }
    }
    
    /**
     * 缓存击穿保护
     */
    public function getCacheWithLock($config, $arguments, callable $callback, int $lockTtl = 10)
    {
        try {
            $this->setDrive($config);
            
            // 黑白名单检查
            if (!$this->isAllowedToCache()) {
                return $callback();
            }
            
            // 检查是否启用锁
            $useLock = $this->getConfigValue('use_lock', true);
            if (!$useLock) {
                // 不使用锁，直接走普通逻辑
                $cache = $this->getCache($config, $arguments);
                if ($cache !== null) {
                    return $cache;
                }
                
                $result = $callback();
                if ($result !== null && $result !== false) {
                    $this->setCache($config, $arguments, $result);
                }
                return $result;
            }
            
            // 使用锁的逻辑
            $cache = $this->getCache($config, $arguments);
            if ($cache !== null) {
                return $cache;
            }
            
            $cacheKey = $this->generateCacheKey($arguments);
            $lockKey = $cacheKey . ':lock';
            $lockValue = uniqid(php_uname('n'), true);
            
            $locked = $this->drive->set($lockKey, $lockValue, ['NX', 'EX' => $lockTtl]);
            
            if ($locked) {
                try {
                    $cache = $this->getCache($config, $arguments);
                    if ($cache !== null) {
                        return $cache;
                    }
                    
                    $result = $callback();
                    
                    if ($result !== null && $result !== false) {
                        $this->setCache($config, $arguments, $result);
                    }
                    
                    return $result;
                } finally {
                    $this->releaseLock($lockKey, $lockValue);
                }
            } else {
                for ($i = 0; $i < 3; $i++) {
                    usleep(50000);
                    $cache = $this->getCache($config, $arguments);
                    if ($cache !== null) {
                        return $cache;
                    }
                }
                
                return $callback();
            }
            
        } catch (\Throwable $e) {
            $this->getLogger()->error("Cache lock error: " . $e->getMessage());
            return $callback();
        }
    }
    
    /**
     * 安全释放锁
     */
    private function releaseLock($lockKey, $lockValue)
    {
        $script = <<<LUA
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
else
    return 0
end
LUA;
        
        try {
            $this->drive->eval($script, [$lockKey, $lockValue], 1);
        } catch (\Throwable $e) {
            // 释放锁失败不影响业务
        }
    }
    
    /**
     * 优化的 setDrive
     */
    public function setDrive($config)
    {
        if ($this->drive !== null && $this->config === $config) {
            return $this->dataKey;
        }
        
        $this->config = $config;
        
        switch ($config->drive) {
            case 'redis':
                $container = ApplicationContext::getContainer();
                $this->drive = $container->get(RedisFactory::class)->get($config->settings ?? 'default');
                break;
        }
        
        if ($config->group) {
            $this->dataKey = $this->cacheKeys . $config->group . ':' . $config->prefix . ':';
        } else {
            $this->dataKey = $this->cacheKeys . $config->prefix . ':';
        }
        
        return $this->dataKey;
    }
    
    /**
     * 调试方法：查看某个 group 有多少缓存
     */
    public function getGroupCacheCount($config): int
    {
        try {
            $this->setDrive($config);
            $groupSetKey = $this->getGroupSetKey();
            return $this->drive->sCard($groupSetKey);
        } catch (\Throwable $e) {
            return 0;
        }
    }
    
    /**
     * 调试方法：列出某个 group 的所有缓存键
     */
    public function listGroupCacheKeys($config, int $limit = 10): array
    {
        try {
            $this->setDrive($config);
            $groupSetKey = $this->getGroupSetKey();
            return $this->drive->sRandMember($groupSetKey, $limit);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
