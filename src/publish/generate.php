<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    'general' => [
        'app' => 'http',
        'controller' => 'App\Controller',
        'request' => 'App\Request',
        'repository' => 'App\Repository',
        'service' => 'App\Services',
        'model' => 'App\Models',
        'base' => 'App\Base',
        'test' => 'App\Test',
        'intermediate_table' => ['commons'],
    ],

    'code' => [
        200000 => '请求成功',
        200001 => '请求参数异常',
        200002 => '查询数据异常',
        200004 => '操作数据异常',

        200005 => '获取手机号码绑定账户完成注册',

        // token
        300001 => 'token 过期',
        300002 => 'token 无效',
        300003 => '缺少token',
        300004 => '用户不存在',

        300005 => '无法重复提交数据,请稍后',
        300006 => '编号不存在',

        // 请求成功 传输数据无法找到
        403001 => '无法找到数据',
        403002 => '无法找到数据',
        403017 => '临近定时时间不能取消发送任务',
        403018 => '临近定时时间不能修改发送任务',
        403019 => '超过发送时间不能发送',
        403020 => '缺少发表记录ID参数',
        416001 => '添加成功,审核中,请耐心等待',
        416002 => '签名添加失败',

        503001 => '上传文件的格式不正确',
        503002 => '同步成功-记录保存失败',
        503003 => '权限错误',
        503004 => '保存失败',

        // 系统出错
        504000 => '系统维护中',
        504001 => '网络出现异常,请稍后再试',
    ],
];
