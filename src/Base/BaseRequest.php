<?php

namespace App\Lib\Base;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Attribute;

abstract class BaseRequest
{
    #[Inject]
    protected ValidatorFactoryInterface $validationFactory;
    #[Inject]
    protected RequestInterface $request;

    public function validation($rule,$message): array
    {
        $validator = $this->validationFactory->make(
            $this->request->all(),
            $rule,
            $message
        );

        if ($validator->fails()){
            $errorMessage = $validator->errors()->first();
            return [false,$errorMessage];
        }

        return [true,'验证成功'];
    }

}