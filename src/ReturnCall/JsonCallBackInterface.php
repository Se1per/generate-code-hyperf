<?php

declare(strict_types=1);

// namespace App\Base\src;
namespace Japool\Genconsole\ReturnCall;
 
use App\Constants\CodeConstants;
use Psr\Http\Message\ResponseInterface;

interface JsonCallBackInterface
{
    /**
     * 返回类
     * @param CodeConstants|int|string $code
     * @param string|null $msg
     * @param $data
     * @param $count
     * @param $custom
     * @return string|ResponseInterface
     */
    public function JsonMain(CodeConstants|int|string $code, string $msg = null, $data = null, $count = null , $custom = null): string|\Psr\Http\Message\ResponseInterface;
}