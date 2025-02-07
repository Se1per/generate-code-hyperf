<?php

declare(strict_types=1);

// namespace App\Base\src;
namespace Japool\Genconsole\JsonCall;
 
use App\Constants\JsonCodeConstants;
use Psr\Http\Message\ResponseInterface;

interface JsonCallBackInterFace
{
    /**
     * 返回类
     * @param JsonCodeConstants|int|string $code
     * @param string|null $msg
     * @param $data
     * @param $count
     * @param $custom
     * @return string|ResponseInterface
     */
    public function JsonMain(JsonCodeConstants|int|string $code, string $msg = null, $data = null, $count = null , $custom = null): string|\Psr\Http\Message\ResponseInterface;
}