<?php

namespace Japool\Genconsole\Cache\Aspect;

use App\Base\src\LogMonMain;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Japool\Genconsole\Cache\Annotation\CacheAnnotation;
use Japool\Genconsole\Cache\src\BufferDrive;
use Hyperf\Config\Annotation\Value;
use Psr\Log\LoggerInterface;

#[Aspect]
class CacheAspect extends AbstractAspect
{
    #[Inject]
    private ?LogMonMain $logMonMain;

    #[Inject]
    private ?BufferDrive $bufferDrive;
    
    #[Inject]
    private LoggerInterface $logger;

    #[Value("generator.cache.enable")]
    private $cacheEnable;
    
    #[Value("generator.cache.use_lock")]
    private $useLock = true; // 是否使用锁防止击穿

    public array $classes = [];

    public array $annotations = [
        CacheAnnotation::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        // 缓存未启用，直接执行
        if (!$this->cacheEnable) {
            return $proceedingJoinPoint->process();
        }
        
        try {
            $className = $proceedingJoinPoint->className;
            $method = $proceedingJoinPoint->methodName;
            $arguments = $proceedingJoinPoint->arguments['keys'] ?? [];

            $reflect = $proceedingJoinPoint->getAnnotationMetadata();
            $annotation = $reflect->method[CacheAnnotation::class];
            
            // 使用锁保护
            if ($this->useLock) {
                return $this->bufferDrive->getCacheWithLock(
                    $annotation,
                    $arguments['data'] ?? $arguments,
                    function() use ($proceedingJoinPoint) {
                        return $proceedingJoinPoint->process();
                    }
                );
            }

            // 尝试从缓存获取
            $cache = $this->bufferDrive->getCache(
                $annotation, 
                $arguments['data'] ?? $arguments
            );

            if ($cache !== null) {
                return $cache;
            }

            // 执行实际方法
            $result = $proceedingJoinPoint->process();

            // 只缓存成功的结果（可根据业务调整）
            if ($result !== null && $result !== false) {
                $this->bufferDrive->setCache(
                    $annotation, 
                    $arguments['data'] ?? $arguments, 
                    $result
                );
            }

            return $result;
            
        } catch (\Throwable $e) {
            // 缓存异常不应影响业务
            $this->logger->error("Cache aspect error: " . $e->getMessage(), [
                'class' => $className ?? 'unknown',
                'method' => $method ?? 'unknown',
                'exception' => $e
            ]);
            
            return $proceedingJoinPoint->process();
        }
    }
}