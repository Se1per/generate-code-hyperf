<?php

declare(strict_types=1);

namespace Japool\Genconsole\Aspect;

use Japool\Genconsole\Logger\MonitorExecutionAnnotation;
use App\Event\SlowExecutionEvent;
use Hyperf\Context\Context;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Annotation\Aspect;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

#[Aspect]
class MonitorExecutionAspect extends AbstractAspect
{
    public array $annotations = [
        MonitorExecutionAnnotation::class,
    ];

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(ContainerInterface $container)
    {
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
    }

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $annotation = $this->getAnnotation($proceedingJoinPoint);
   
        if (!$annotation instanceof MonitorExecutionAnnotation) {
            return $proceedingJoinPoint->process();
        }

        $startTime = microtime(true);
        $className = $proceedingJoinPoint->className;
        $methodName = $proceedingJoinPoint->methodName;

        $sqlCollectorKey = 'monitor_sql_collector_' . uniqid();
        Context::set($sqlCollectorKey, []);
        Context::set('current_sql_collector', $sqlCollectorKey);
        
        try {
            $result = $proceedingJoinPoint->process();
            $hasError = false;
        } catch (\Throwable $e) {
            $hasError = true;
            throw $e;
        } finally {
            $executionTime = (microtime(true) - $startTime) * 1000;
            $collectedSqls = Context::get($sqlCollectorKey, []);
            Context::destroy($sqlCollectorKey);
            Context::destroy('current_sql_collector');
            
            $isSlow = $executionTime >= $annotation->threshold;

            if ($isSlow || $annotation->alwaysLog || $hasError) {
                // 触发事件
                $this->eventDispatcher->dispatch(new SlowExecutionEvent(
                    $annotation->name ?? "{$className}::{$methodName}",
                    $executionTime,
                    $collectedSqls,
                    [
                        'class' => $className,
                        'method' => $methodName,
                        'threshold' => $annotation->threshold,
                        'has_error' => $hasError,
                    ]
                ));
            }
        }

        return $result;
    }

    private function getAnnotation(ProceedingJoinPoint $proceedingJoinPoint): ?MonitorExecutionAnnotation
    {
        $metadata = $proceedingJoinPoint->getAnnotationMetadata();
        return $metadata->method[MonitorExecutionAnnotation::class] ?? null;
    }
}