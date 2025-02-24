<?php

namespace Japool\Genconsole\ReturnCall\Aspect;

use Japool\Genconsole\ReturnCall\Annotation\ReturnAnnotation;
use Japool\Genconsole\ReturnCall\JsonCallBackInterface;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

#[Aspect]
class ReturnCallAspect extends AbstractAspect
{
    #[Inject]
    protected JsonCallBackInterface $JsonCallBack;

    public array $classes = [
    ];

    public array $annotations = [
        ReturnAnnotation::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $result = $proceedingJoinPoint->process();//8888

        $reflect = $proceedingJoinPoint->getAnnotationMetadata();

        $annotation = $reflect->method[ReturnAnnotation::class];

        switch ($annotation->mode)
        {
            case 'json':
                return $this->JsonCallBack->JsonMain(...$result);
        }

        return $result;
    }
}