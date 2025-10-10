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

use Monolog\Logger;

return [
    'general' => [
        'app' => 'http',
        'controller' => 'App\Controller',
        'manager' => 'App\Manager',
        'request' => 'App\Request',
        'repository' => 'App\Repository',
        'service' => 'App\Services',
        'model' => 'App\Models',
        'base' => 'App\Base',
        'test' => 'App\Test',
        'intermediate_table' => ['commons'],
    ],

    'jwt' => [
        // 64位
        'secret' => \Hyperf\Support\env('JWT_SECRET', 'azqwrzbie3d5d0061126d0ca0320daf761444bdbe52ba4fac580932ce0ddc9ad'),
        'algorithm' => 'HS256',
        'exp' => \Hyperf\Support\env('JWT_TOKEN_TIME_OUT', 3600),
        'exclude' => [
            '/api/login/loginApi',
        ],
        'x-forwarded-for' => [
            '127.0.0.1'
        ]
    ],

    'logger' => [
        // ========== 以下是自定义配置（给 LoggerFactory 使用） ==========
        // API 日志
        'api' => [
            'level' => Logger::INFO, //日志级别 (DEBUG|INFO|WARNING|ERROR)
            'max_files' => 14, //保留天数
            'dir' => BASE_PATH . '/runtime/logs/api',//目录
            'use_json' => false, //是否使用 JSON 格式
            // 'extra' => ['app' => 'aaa'],     // 额外字段（可选）
        ],

        // 错误日志
        'api-error' => [
            'level' => Logger::ERROR,
            'max_files' => 14,  // 保留3个月
            'dir' => BASE_PATH . '/runtime/logs/api',
            'use_json' => false,
        ],
        
        // 请求日志
        'request' => [
            'level' => Logger::INFO,
            'max_files' => 7,
            'dir' => BASE_PATH . '/runtime/logs/request',
            'use_json' => false,
        ],

        // 业务日志
        'business' => [
            'level' => Logger::INFO,
            'max_files' => 7,
            'dir' => BASE_PATH . '/runtime/logs/business',
            'use_json' => false,
        ],

        // SQL 日志
        'sql' => [
            'level' => Logger::INFO,
            'max_files' => 7,
            'dir' => BASE_PATH . '/runtime/logs/sql',
            'use_json' => true,
        ],

        // 方法执行日志
        'execution' => [
            'dir' => BASE_PATH . '/runtime/logs/execution',
            'level' => Logger::INFO,
            'max_files' => 30,
            'use_json' => false,
        ],

        // 慢方法执行日志
        'slow-execution' => [
            'dir' => BASE_PATH . '/runtime/logs/slow-execution',
            'level' => Logger::WARNING,
            'max_files' => 30,
            'use_json' => true, // JSON 格式便于分析
        ],
        // 慢方法执行日志
        'slow-execution-auto' => [
            'dir' => BASE_PATH . '/runtime/logs/slow-execution-auto',
            'level' => Logger::WARNING,
            'max_files' => 30,
            'use_json' => true, // JSON 格式便于分析
        ],

        // 支付日志（重要）
        'payment' => [
            'level' => Logger::INFO,
            'max_files' => 365,  // 保留1年
            'dir' => BASE_PATH . '/runtime/logs/payment',
            'use_json' => true,  // JSON 格式便于分析
        ],

        // 自定义日志
        'custom' => [
            'level' => Logger::INFO,
            'max_files' => 7,
            'dir' => BASE_PATH . '/runtime/logs',
            'use_json' => true,
        ],
    ],
    
    //慢查询日志抓取 单位 ms 
    'slow_query_threshold' => 100,
];
