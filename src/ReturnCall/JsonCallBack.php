<?php

declare(strict_types=1);

// namespace App\Base\src;
namespace Japool\Genconsole\ReturnCall;

use Japool\Genconsole\ReturnCall\JsonCallBackInterface;
use App\Constants\CodeConstants;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\ResponseInterface;

class JsonCallBack implements JsonCallBackInterface
{
    #[Inject]
    protected ResponseInterface $response;

    public function JsonMain(CodeConstants|int|string $code, string $msg = null, $data = null, $count = null , $custom = null): string|\Psr\Http\Message\ResponseInterface
    {
        if ($code instanceof CodeConstants) {
            $message = $code->getMessage();
        } else {
            $message = CodeConstants::CODE_ERROR->getMessage([$code]);

            return $this->response->json([
                'status' => 'error',
                'code' => CodeConstants::CODE_ERROR->value,
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
            'total' => $count,
        ]));

        if(!empty($custom)){
            $layout['custom'] = $custom;
        }

        return $this->response->json($layout);
    }
}