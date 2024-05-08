<?php

namespace Japool\Genconsole\Base\Interface;

interface JsonCallBackInterface
{
    public function JsonMain(int $code, string $msg = null, $data = null, $count = null , $custom = null);
}