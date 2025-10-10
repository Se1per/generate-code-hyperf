<?php
declare(strict_types=1);
namespace Japool\Genconsole\Logger\Event;

/**
 * 慢执行事件类
 * 
 * 用于记录和传递应用中的慢执行信息，包括执行时间、SQL查询列表等
 * 通常用于性能监控和日志记录
 */
class SlowExecutionEvent
{
    /**
     * 执行操作的名称或标识
     *
     * @var string
     */
    public string $name;

    /**
     * 执行耗时（单位：秒）
     *
     * @var float
     */
    public float $time;

    /**
     * 执行过程中的SQL查询列表
     *
     * @var array
     */
    public array $sqlList;

    /**
     * 额外的扩展信息
     *
     * @var array
     */
    public array $extra;

    /**
     * 构造函数
     * @$this->eventDispatcher->dispatch(new SlowExecutionEvent('用户查询'));
     * @param string $name 执行操作的名称或标识
     * @param float $time 执行耗时（单位：毫秒）
     * @param array $sqlList 执行过程中的SQL查询列表，默认为空数组
     * @param array $extra 额外的扩展信息，默认为空数组
     */
    public function __construct(
        string $name,
        float $time = 100,
        array $sqlList = [],
        array $extra = []
    ) {
        $this->name = $name;
        $this->time = $time;
        $this->sqlList = $sqlList;
        $this->extra = $extra;
    }
}
