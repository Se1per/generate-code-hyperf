<?php

namespace Japool\Genconsole\Console\Services;

/**
 * 生成器配置服务
 * 职责：管理生成器配置，避免硬编码
 */
class GeneratorConfigService
{
    /**
     * 生成器配置定义
     * 格式：[配置key, 命令名称, 类后缀, 描述, 是否需要app命名空间]
     */
    private const GENERATORS = [
        'controller' => [
            'key' => 'controller',
            'command' => 'generate:crud-controller',
            'suffix' => 'Controller',
            'description' => '控制器层',
            'needsApp' => true,
            'order' => 1,
        ],
        'manager' => [
            'key' => 'manager',
            'command' => 'generate:crud-manager',
            'suffix' => 'Manager',
            'description' => '业务层',
            'needsApp' => false,
            'order' => 2,
        ],
        'model' => [
            'key' => 'model',
            'command' => 'generate:crud-model',
            'suffix' => '',
            'description' => '模型层',
            'needsApp' => false,
            'order' => 3,
        ],
        'request' => [
            'key' => 'request',
            'command' => 'generate:crud-request',
            'suffix' => 'Request',
            'description' => '验证层',
            'needsApp' => false,
            'order' => 4,
        ],
        'service' => [
            'key' => 'service',
            'command' => 'generate:crud-service',
            'suffix' => 'Service',
            'description' => '服务层',
            'needsApp' => false,
            'order' => 5,
        ],
        'repository' => [
            'key' => 'repository',
            'command' => 'generate:crud-repository',
            'suffix' => 'Repository',
            'description' => '数据层',
            'needsApp' => false,
            'order' => 6,
        ],
    ];

    /**
     * 测试文件配置
     */
    private const TEST_CONFIG = [
        'key' => 'test',
        'command' => 'generate:generateTest',
        'suffix' => 'ControllerTest',
        'description' => '测试实例',
        'needsApp' => false,
        'isTest' => true,
    ];

    /**
     * 获取所有生成器配置
     */
    public function getAllGenerators(): array
    {
        $generators = self::GENERATORS;
        
        // 按order排序
        uasort($generators, fn($a, $b) => $a['order'] <=> $b['order']);
        
        return $generators;
    }

    /**
     * 获取单个生成器配置
     */
    public function getGenerator(string $key): ?array
    {
        return self::GENERATORS[$key] ?? null;
    }

    /**
     * 获取测试配置
     */
    public function getTestConfig(): array
    {
        return self::TEST_CONFIG;
    }

    /**
     * 检查生成器是否存在
     */
    public function hasGenerator(string $key): bool
    {
        return isset(self::GENERATORS[$key]);
    }

    /**
     * 获取所有生成器的键
     */
    public function getGeneratorKeys(): array
    {
        return array_keys(self::GENERATORS);
    }

    /**
     * 获取生成器配置（用于兼容旧代码）
     */
    public function toCompatibleArray(): array
    {
        $compatible = [];
        
        foreach (self::GENERATORS as $generator) {
            $compatible[] = [
                $generator['key'],
                $generator['command'],
                $generator['suffix'],
                $generator['description'],
                $generator['needsApp'],
            ];
        }
        
        return $compatible;
    }
}

