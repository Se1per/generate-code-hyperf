<?php

namespace Japool\Genconsole\Cache\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Japool\Genconsole\Cache\Annotation\CacheEventAnnotation;
use Japool\Genconsole\Cache\src\BufferDrive;

#[Aspect]
class CacheEventAspect extends AbstractAspect
{
    #[Inject]
    private ?BufferDrive $bufferDrive;

    public array $classes = [
    ];

    public array $annotations = [
        CacheEventAnnotation::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $result = $proceedingJoinPoint->process();//8888
        
        $className = $proceedingJoinPoint->className;
        $method = $proceedingJoinPoint->methodName;
        $arguments = $proceedingJoinPoint->arguments['keys'];

        $reflect = $proceedingJoinPoint->getAnnotationMetadata();

        $annotation = $reflect->method[CacheEventAnnotation::class];

        $cache = $this->bufferDrive->removeCache($annotation);

        return $result;
    }
}