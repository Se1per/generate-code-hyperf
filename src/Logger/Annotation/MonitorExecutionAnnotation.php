<?php

declare(strict_types=1);

// namespace App\Annotation;
namespace Japool\Genconsole\Logger\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 方法执行监控注解慢sql
 * 用于监控方法执行时间和调用链
 * 
 * 使用示例：
 * #[MonitorExecutionAnnotation(threshold: 100, name: "用户查询")]
 */
#[Attribute]
class MonitorExecutionAnnotation extends AbstractAnnotation
{
    /**
     * @param float $threshold 慢执行阈值（毫秒），超过此值会记录为慢查询
     * @param string|null $name 监控名称，用于日志识别
     * @param bool $alwaysLog 是否总是记录（即使未超过阈值）
     * @param string $level 日志级别：info, warning, error
     */
    public function __construct(
        public float $threshold = 100.0,
        public ?string $name = null,
        public bool $alwaysLog = false,
        public string $level = 'warning'
    ) {
    }
}