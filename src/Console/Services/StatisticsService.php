<?php

namespace Japool\Genconsole\Console\Services;

/**
 * 统计服务
 * 职责：管理生成/删除统计数据
 */
class StatisticsService
{
    private array $generated = [];
    private array $skipped = [];
    private array $deleted = [];
    private array $notFound = [];
    private int $totalTables = 0;

    /**
     * 设置总表数
     */
    public function setTotalTables(int $count): void
    {
        $this->totalTables = $count;
    }

    /**
     * 获取总表数
     */
    public function getTotalTables(): int
    {
        return $this->totalTables;
    }

    /**
     * 记录生成的文件
     */
    public function addGenerated(string $table, string $type, string $class): void
    {
        $this->generated[] = [
            'table' => $table,
            'type' => $type,
            'class' => $class,
        ];
    }

    /**
     * 记录跳过的文件
     */
    public function addSkipped(string $table, string $type, string $class): void
    {
        $this->skipped[] = [
            'table' => $table,
            'type' => $type,
            'class' => $class,
        ];
    }

    /**
     * 记录删除的文件
     */
    public function addDeleted(string $table, string $type, string $class, string $path): void
    {
        $this->deleted[] = [
            'table' => $table,
            'type' => $type,
            'class' => $class,
            'path' => $path,
        ];
    }

    /**
     * 记录未找到的文件
     */
    public function addNotFound(string $table, string $type, string $class, string $path): void
    {
        $this->notFound[] = [
            'table' => $table,
            'type' => $type,
            'class' => $class,
            'path' => $path,
        ];
    }

    /**
     * 获取生成的文件列表
     */
    public function getGenerated(): array
    {
        return $this->generated;
    }

    /**
     * 获取跳过的文件列表
     */
    public function getSkipped(): array
    {
        return $this->skipped;
    }

    /**
     * 获取删除的文件列表
     */
    public function getDeleted(): array
    {
        return $this->deleted;
    }

    /**
     * 获取未找到的文件列表
     */
    public function getNotFound(): array
    {
        return $this->notFound;
    }

    /**
     * 获取生成文件总数
     */
    public function getTotalGenerated(): int
    {
        return count($this->generated);
    }

    /**
     * 获取跳过文件总数
     */
    public function getTotalSkipped(): int
    {
        return count($this->skipped);
    }

    /**
     * 获取删除文件总数
     */
    public function getTotalDeleted(): int
    {
        return count($this->deleted);
    }

    /**
     * 获取未找到文件总数
     */
    public function getTotalNotFound(): int
    {
        return count($this->notFound);
    }

    /**
     * 获取处理的总文件数
     */
    public function getTotalFiles(): int
    {
        return count($this->generated) + count($this->skipped) + count($this->deleted) + count($this->notFound);
    }

    /**
     * 重置统计数据
     */
    public function reset(): void
    {
        $this->generated = [];
        $this->skipped = [];
        $this->deleted = [];
        $this->notFound = [];
        $this->totalTables = 0;
    }

    /**
     * 获取所有统计数据
     */
    public function toArray(): array
    {
        return [
            'generated' => $this->generated,
            'skipped' => $this->skipped,
            'deleted' => $this->deleted,
            'notFound' => $this->notFound,
            'total_tables' => $this->totalTables,
            'total_generated' => $this->getTotalGenerated(),
            'total_skipped' => $this->getTotalSkipped(),
            'total_deleted' => $this->getTotalDeleted(),
            'total_notFound' => $this->getTotalNotFound(),
            'total_files' => $this->getTotalFiles(),
        ];
    }
}

