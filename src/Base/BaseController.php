<?php

namespace Japool\Genconsole\Base;

use App\Controller\AbstractController;
use App\Lib\Base\Interface\JsonCallBackInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Container\ContainerInterface;

abstract class BaseController extends AbstractController
{
    #[Inject]
    protected JsonCallBackInterface $JsonCallBack;
    #[Inject]
    protected ContainerInterface $container;
    #[Inject]
    protected RequestInterface $request;
}