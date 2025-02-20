<?php

namespace Japool\Genconsole\Cache\src;

use Hyperf\Redis\RedisFactory;
use Hyperf\Context\ApplicationContext;
use Hyperf\Redis\Redis;

class BufferDrive
{
    public $drive;

    public $cacheKeys = 'CacheGroup:';

    public $dataKey = null;

    public function getCache($config, $arguments)
    {
        $this->setDrive($config);
        $arguments = json_encode($arguments);
        $keyArgum = md5($arguments);
 
        $data = $this->drive->get($this->dataKey.$keyArgum);

        if(!empty($data)){
            $data = json_decode($data, true);
        }

        return $data;
    }

    public function setCache($config,$arguments,$result)
    {
        $this->setDrive($config);
        $arguments = json_encode($arguments);
        $keyArgum = md5($arguments);

        return $this->drive->setex($this->dataKey.$keyArgum,$config->ttl,json_encode($result));
    }

    public function removeCache($config)
    {
        $this->setDrive($config);

        return $this->delCacheKeys();
    }

    public function getCacheKeys($dataKey)
    {
        $luaScript = '
            local cursor = "0"
            local pattern = ARGV[1] .. "*"
            local result = {}
            repeat
                local res = redis.call("SCAN", cursor, "MATCH", pattern, "COUNT", 100)
                cursor = res[1]
                for _, key in ipairs(res[2]) do
                    if string.sub(key, 1, string.len(ARGV[1])) == ARGV[1] then
                        table.insert(result, key)
                    end
                end
            until cursor == "0"
            return result
        ';

        $args = [$dataKey];
        return $this->drive->eval($luaScript, $args, 0);
    }

    public function delCacheKeys()
    {
        $luaScript = '
            local cursor = "0"
            local pattern = ARGV[1] .. "*"
            local keysToDelete = {}
            repeat
                local res = redis.call("SCAN", cursor, "MATCH", pattern, "COUNT", 100)
                cursor = res[1]
                for _, key in ipairs(res[2]) do
                    if string.sub(key, 1, string.len(ARGV[1])) == ARGV[1] then
                        table.insert(keysToDelete, key)
                    end
                end
            until cursor == "0"
            if #keysToDelete > 0 then
                redis.call("UNLINK", unpack(keysToDelete))
            end
    
            return #keysToDelete
        ';

        $args = [$this->dataKey];
        return $this->drive->eval($luaScript, $args, 0);
    }

    public function setDrive($config)
    {
        switch ($config->drive) {
            case 'redis':
                $container = ApplicationContext::getContainer();
                $this->drive = $container->get(RedisFactory::class)->get($config->settings);
            break;
        }

        if ($config->group) {
            $this->dataKey =  $this->cacheKeys . $config->group . ':' . $config->prefix . ':';
        } else {
            $this->dataKey = $this->cacheKeys . $config->prefix . ':';
        }

        return $this->dataKey;
    }

}