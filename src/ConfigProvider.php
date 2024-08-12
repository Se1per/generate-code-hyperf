<?php

namespace Japool\Genconsole;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            // 合并到  config/autoload/dependencies.php 文件
            'dependencies' => [],
            // 合并到  config/autoload/annotations.php 文件
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            // 默认 Command 的定义，合并到 Hyperf\Contract\ConfigInterface 内，换个方式理解也就是与 config/autoload/commands.php 对应
            'commands' => [],
            // 与 commands 类似
            'listeners' => [],
            // 组件默认配置文件，即执行命令后会把 source 的对应的文件复制为 destination 对应的的文件
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for generate', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/generate.php',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/config/autoload/generate.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'ApiException',
                    'description' => 'ApiException generate', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/stub/ApiExceptionHandler.stub',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/app/Middleware/Handler/ApiExceptionHandler.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'JsonCallBackInterFace',
                    'description' => 'JsonCallBackInterFace generate', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/stub/JsonCallBackInterface.stub',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/app/Base/src/JsonCallBackInterface.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'JsonCallBack',
                    'description' => 'JsonCallBack generate', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/stub/JsonCallBack.stub',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/app/Base/src/JsonCallBack.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'RepositoryPackage',
                    'description' => 'RepositoryPackage generate', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/stub/RepositoryPackage.stub',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/app/Base/src/RepositoryPackage.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'BaseService',
                    'description' => 'BaseService generate', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/stub/BaseService.stub',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/app/Base/BaseService.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'BaseRepository',
                    'description' => 'BaseRepository generate', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/stub/BaseRepository.stub',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/app/Base/BaseRepository.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'BaseModel',
                    'description' => 'BaseModel generate', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/stub/BaseModel.stub',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/app/Base/BaseRepository.php', // 复制为这个路径下的该文件
                ],
                [
                    'id' => 'BaseController',
                    'description' => 'BaseController generate', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/publish/stub/BaseController.stub',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/app/Base/BaseController.php', // 复制为这个路径下的该文件
                ],
            ],
            // 亦可继续定义其它配置，最终都会合并到与 ConfigInterface 对应的配置储存器中
        ];
    }
}