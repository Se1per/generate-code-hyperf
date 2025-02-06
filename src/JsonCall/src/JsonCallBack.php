<?php

declare(strict_types=1);

// namespace App\Base\src;
namespace Japool\Genconsole\JsonCall\src;

use Japool\Genconsole\JsonCall\JsonCallBackInterface;
use App\Constants\JsonCodeConstants;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\ResponseInterface;

class JsonCallBack 
{
    #[Inject]
    protected ResponseInterface $response;

    public function JsonMain(JsonCodeConstants|int|string $code, string $msg = null, $data = null, $count = null , $custom = null): string|\Psr\Http\Message\ResponseInterface
    {
        if ($code instanceof JsonCodeConstants) {
            $message = $code->getMessage();
        } else {
            $message = JsonCodeConstants::CODE_ERROR->getMessage([$code]);

            return $this->response->json([
                'status' => 'error',
                'code' => $code->value,
                'message' => $message,
            ]);
        }

        if(!is_null($msg)){
            $message = $message.':'.$msg;
        }

        switch ($code->value)
        {
            case 200000:
                $status = 'success';
            break;
            case 200001:
            case 200002:
            case 200003:
                $status = 'warning';
            break;
            default:
                $status = 'error';
            break;
        }

        $layout = ($data === null ? [
            'status' => $status,
            'code' => $code->value,
            'message' => $message,
        ] : (($count === null) ? [
            'status' => $status,
            'code' => $code->value,
            'message' => $message,
            'data' => $data,
        ] : [
            'status' => $status,
            'code' => $code->value,
            'message' => $message,
            'data' => $data,
            'count' => $count,
        ]));

        if(!empty($custom)){
            $layout['custom'] = $custom;
        }

        return $this->response->json($layout);
    }
}