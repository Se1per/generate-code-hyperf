<?php

namespace Japool\Genconsole\Console\src;

/**
 * Model 数据构建 Trait
 * 职责：构建 Model 相关数据（从 AutoCodeHelp 中提取）
 */
trait ModelDataBuilderTrait
{
    /**
     * 生成 Model 可编辑数据 (MySQL)
     */
    public function makeModelData($column, &$primaryKey, &$fillAble, &$softDeletes, &$keyGet): bool
    {
        // 检测软删除
        if ($column->Field === 'deleted_at') {
            $softDeletes = true;
        }

        // 跳过时间戳字段
        if (in_array($column->Field, ['deleted_at', 'created_at', 'updated_at'])) {
            return true;
        }

        $columnName = $column->Field;

        // 处理主键
        if (!$keyGet && $column->Key === 'PRI') {
            $keyGet = true;
            if ($primaryKey === null) {
                $primaryKey = "'{$columnName}'";
            }
            return true;
        }

        // 添加到可填充字段
        $fillAble .= "'{$columnName}',";

        return true;
    }

    /**
     * 生成 Model 可编辑数据 (PostgreSQL)
     */
    public function makeModelDataPgsql($column, &$primaryKey, &$fillAble, &$softDeletes, &$keyGet): bool
    {
        $columnName = $column->column_name;

        // 检测软删除
        if ($columnName === 'deleted_at') {
            $softDeletes = true;
        }

        // 跳过时间戳字段
        if (in_array($columnName, ['deleted_at', 'created_at', 'updated_at'])) {
            return true;
        }

        // 处理主键
        if (!$keyGet && $column->is_primary_key === 'YES') {
            $keyGet = true;
            if ($primaryKey === null) {
                $primaryKey = "'{$columnName}'";
            }
            return true;
        }

        // 添加到可填充字段
        $fillAble .= "'{$columnName}',";

        return true;
    }
}

