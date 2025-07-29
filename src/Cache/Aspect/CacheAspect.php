<?php

namespace Japool\Genconsole\Cache\Aspect;

use App\Base\src\LogMonMain;
use Hyperf\Cache\AnnotationManager;
use Hyperf\Cache\CacheManager;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Japool\Genconsole\Cache\Annotation\CacheAnnotation;
use Japool\Genconsole\Cache\src\BufferDrive;
use Japool\Genconsole\JsonCall\Annotation\ReturnAnnotation;
use Hyperf\Config\Annotation\Value;

#[Aspect]
class CacheAspect extends AbstractAspect
{
    #[Inject]
    private ?LogMonMain $logMonMain;

    #[Inject]
    private ?BufferDrive $bufferDrive;

    #[Value("generator.cache.enable")]
    private $cache;

    public array $classes = [
    ];

    public array $annotations = [
        CacheAnnotation::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $className = $proceedingJoinPoint->className;
        $method = $proceedingJoinPoint->methodName;
        $arguments = $proceedingJoinPoint->arguments['keys'];

        $reflect = $proceedingJoinPoint->getAnnotationMetadata();

        $annotation = $reflect->method[CacheAnnotation::class];

        $cache = $this->bufferDrive->getCache($annotation,$arguments['data']);

        if($cache && $this->cache){
            return $cache;
        }

        $result = $proceedingJoinPoint->process();//8888

        $this->bufferDrive->setCache($annotation,$arguments['data'],$result);
        
        return $result;
    }
}