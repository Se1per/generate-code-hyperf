<?php


namespace Japool\Genconsole\RequestLog;

use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = microtime(true);

        $requestId = uniqid('', true);
        $requestId = str_replace('.', '-', $requestId);

        $heardArr = [
            'request_id'=>$requestId,//TODO 此处可以利用redis生成
            'server'=>$request->getServerParams(),
            'body' => $request->getParsedBody(),
            'headers' => $request->getHeaders(),
            'api_start'=>$startTime,
        ];
         
        Context::set('request_log', $heardArr);

        $result = $handler->handle($request);

        $getRequest = Context::get('request_log');

        //记录用户
//        $user  = Context::get('userToken');
//        if($user){
//            $user = json_decode($user,true);
//            $userId = $user['id'];
//        }else{
//            $userId = null;
//        }

        //记录返回值
        $jsonContent = json_decode($result->getBody()->getContents(),true);
        
        if($jsonContent['code'] == '200000'){
            $status = 1;
        }else{
            $status = 0;
        }

        $endTime = microtime(true);
        $requestTime = bcsub((string)$endTime, (string)$startTime, 2);
        $getRequest['api_end'] = $endTime;
        $getRequest['request_time'] = $requestTime;
        $getRequest['result_status'] = $status;
        $getRequest['result_body'] = $jsonContent;

        return $result;
    }
}