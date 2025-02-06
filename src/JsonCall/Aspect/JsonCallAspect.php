<?php

declare(strict_types=1);

// namespace App\Base\src;
namespace Japool\Genconsole\JsonCall\Aspect;


use Japool\Genconsole\JsonCall\Annotation\ReturnAnnotation;
use Japool\Genconsole\JsonCall\JsonCallBackInterface;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

#[Aspect]
class JsonCallAspect extends AbstractAspect
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
        // 切面切入后，执行对应的方法会由此来负责
        // $proceedingJoinPoint 为连接点，通过该类的 process() 方法调用原方法并获得结果
        // 在调用前进行某些处理

        $result = $proceedingJoinPoint->process();//8888

        // 在调用后进行某些处理
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