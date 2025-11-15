<?php

namespace Japool\Genconsole\Help\src;

/**
 * 数组处理工具类 Trait
 * 提供常用的数组操作方法，包括去重、分页、排序、树状结构构建等功能
 * 
 * @package Japool\Genconsole\Help\src
 * 
 * 使用方式:
 * class YourClass {
 *     use ArrayTrait;
 * 
 *     public function example() {
 *         // 使用 trait 中的方法
 *         $tree = $this->generateTree($data);
 *     }
 * }
 */
trait ArrayTrait
{
    /**
     * 二维数组去重
     * 根据指定字段对二维数组进行去重，保留第一次出现的记录
     * 
     * @param array $array 待去重的二维数组
     * @param string $field 作为去重依据的字段名
     * @return array|false 去重后的数组，失败返回 false
     * 
     * @example
     * $data = [
     *     ['id' => 1, 'name' => '张三'],
     *     ['id' => 2, 'name' => '李四'],
     *     ['id' => 1, 'name' => '张三重复']  // 将被去除
     * ];
     * $result = $this->arrayUnique($data, 'id');
     * // 结果: [['id' => 1, 'name' => '张三'], ['id' => 2, 'name' => '李四']]
     */
    public function arrayUnique($array, $field)
    {
        if (empty($array) || !$field) {
            return false;
        }

        // 提取指定字段的所有值并去重
        $fields = array_unique(array_column($array, $field));

        // 过滤数组，只保留去重后字段值对应的记录
        $data = array_filter($array, function ($item) use ($field, $fields) {
            return in_array($item[$field], $fields);
        });

        // 重置数组索引为连续的数字键
        return array_values($data);
    }

    /**
     * 分页参数转换
     * 将页码和每页数量转换为数据库查询所需的 offset 和 limit
     * 
     * @param array $data 包含分页参数的数组，支持 page 和 pageSize 字段
     * @return array 返回转换后的数组，page 字段变为 offset 偏移量
     * 
     * @example
     * // 场景1: 获取第3页，每页10条
     * $params = ['page' => 3, 'pageSize' => 10];
     * $result = $this->pageLimit($params);
     * // 结果: ['page' => 20, 'pageSize' => 10]  
     * // SQL: LIMIT 10 OFFSET 20
     * 
     * @example
     * // 场景2: 使用默认值
     * $params = [];
     * $result = $this->pageLimit($params);
     * // 结果: ['page' => 0, 'pageSize' => 10]
     * // 默认第1页，每页10条
     */
    public function pageLimit(array $data): array
    {
        // 默认第1页
        $data['page'] = $data['page'] ?? 1;

        // 默认每页10条
        $data['pageSize'] = $data['pageSize'] ?? 10;

        // 计算偏移量: (页码 - 1) * 每页数量
        $data['page'] = ($data['page'] - 1) * $data['pageSize'];

        return $data;
    }

    /**
     * 构建树状结构数据
     * 将扁平化的数组数据转换为具有层级关系的树状结构
     * 使用引用传递方式，性能优异，时间复杂度 O(n)
     * 
     * @param array $list 扁平化的原始数组数据
     * @param int $root 根节点的父级ID值，默认为 0
     * @param string $pk 主键字段名，默认为 'id'
     * @param string $pid 父级ID字段名，默认为 'pid'
     * @param string $child 子节点字段名，默认为 'child'
     * @return array 返回树状结构数组
     * 
     * @example
     * // 场景1: 组织架构树
     * $organizations = [
     *     ['id' => 1, 'pid' => 0, 'name' => '总公司'],
     *     ['id' => 2, 'pid' => 1, 'name' => '技术部'],
     *     ['id' => 3, 'pid' => 1, 'name' => '市场部'],
     *     ['id' => 4, 'pid' => 2, 'name' => '研发组'],
     *     ['id' => 5, 'pid' => 2, 'name' => '测试组'],
     * ];
     * $tree = $this->generateTree($organizations);
     * // 结果:
     * // [
     * //     [
     * //         'id' => 1, 'pid' => 0, 'name' => '总公司',
     * //         'child' => [
     * //             [
     * //                 'id' => 2, 'pid' => 1, 'name' => '技术部',
     * //                 'child' => [
     * //                     ['id' => 4, 'pid' => 2, 'name' => '研发组'],
     * //                     ['id' => 5, 'pid' => 2, 'name' => '测试组']
     * //                 ]
     * //             ],
     * //             ['id' => 3, 'pid' => 1, 'name' => '市场部']
     * //         ]
     * //     ]
     * // ]
     * 
     * @example
     * // 场景2: 自定义字段名称和根节点
     * $categories = [
     *     ['cat_id' => 10, 'parent_id' => 5, 'title' => '电子产品'],
     *     ['cat_id' => 11, 'parent_id' => 10, 'title' => '手机'],
     *     ['cat_id' => 12, 'parent_id' => 10, 'title' => '电脑'],
     * ];
     * $tree = $this->generateTree($categories, 5, 'cat_id', 'parent_id', 'children');
     * // 从 parent_id = 5 开始构建，子节点存储在 children 字段
     */
    public function generateTree(array $list, int $root = 0, string $pk = 'id', string $pid = 'pid', string $child = 'child'): array
    {
        $tree = array();
        $packData = array();

        // 第一步: 将数组转换为以主键为索引的关联数组，便于快速查找
        foreach ($list as $data) {
            $packData[$data[$pk]] = $data;
        }

        // 第二步: 遍历数据，建立父子关系
        foreach ($packData as $key => $val) {
            if ($val[$pid] == $root) {
                // 父级ID等于根节点值，说明是顶层节点
                $tree[] = &$packData[$key];
            } else {
                // 将当前节点添加到其父节点的子节点数组中
                // 使用引用传递，避免数据复制，提高性能
                $packData[$val[$pid]][$child][] = &$packData[$key];
            }
        }
        return $tree;
    }


    /**
     * 二维数组按嵌套字段排序
     * 对包含嵌套数组的二维数组进行降序排序
     * 
     * @param array $array 待排序的二维数组
     * @param string $pIndex 父级索引字段名
     * @param string $index 排序字段名
     * @return array 排序后的数组
     * 
     * @example
     * // 按统计数据排序
     * $data = [
     *     ['name' => '用户A', 'stats' => ['score' => 95]],
     *     ['name' => '用户B', 'stats' => ['score' => 88]],
     *     ['name' => '用户C', 'stats' => ['score' => 92]],
     * ];
     * $sorted = $this->sortByByteArray($data, 'stats', 'score');
     * // 结果按 score 降序排列:
     * // [
     * //     ['name' => '用户A', 'stats' => ['score' => 95]],
     * //     ['name' => '用户C', 'stats' => ['score' => 92]],
     * //     ['name' => '用户B', 'stats' => ['score' => 88]],
     * // ]
     */
    public function sortByByteArray($array, $pIndex, $index)
    {
        usort($array, function ($a, $b) use ($index, $pIndex) {
            // 提取嵌套字段的值，如果父级索引不存在则默认为 0
            $valueA = is_array($a[$pIndex]) ? $a[$pIndex][$index] : 0;
            $valueB = is_array($b[$pIndex]) ? $b[$pIndex][$index] : 0;
            
            // 降序排序：大值在前
            return $valueB - $valueA;
        });

        return $array;
    }

    /**
     * 二维数组按指定字段排序
     * 对二维数组按照某个字段进行升序或降序排序
     * 
     * @param array $array 待排序的二维数组
     * @param string $field 排序字段名
     * @param bool $ascending 是否升序排序，true=升序，false=降序，默认为 true
     * @return array 排序后的数组
     * 
     * @example
     * // 场景1: 按价格升序排序
     * $products = [
     *     ['name' => '商品A', 'price' => 99],
     *     ['name' => '商品B', 'price' => 56],
     *     ['name' => '商品C', 'price' => 128],
     * ];
     * $sorted = $this->sortByField($products, 'price', true);
     * // 结果: [商品B(56), 商品A(99), 商品C(128)]
     * 
     * @example
     * // 场景2: 按时间降序排序
     * $logs = [
     *     ['event' => '登录', 'time' => 1633320000],
     *     ['event' => '支付', 'time' => 1633330000],
     *     ['event' => '退出', 'time' => 1633310000],
     * ];
     * $sorted = $this->sortByField($logs, 'time', false);
     * // 结果: 按时间从新到旧排列
     * 
     * @example
     * // 场景3: 按字符串字段排序
     * $users = [
     *     ['name' => '张三', 'level' => 'VIP'],
     *     ['name' => '李四', 'level' => 'SVIP'],
     *     ['name' => '王五', 'level' => 'Normal'],
     * ];
     * $sorted = $this->sortByField($users, 'name');
     * // 按名字升序排列
     * 
     * @author Se1per
     * @date 2023/9/6 10:11
     */
    public function sortByField($array, $field, $ascending = true)
    {
        usort($array, function ($a, $b) use ($field, $ascending) {
            // 使用太空船操作符进行比较
            // 返回 -1 (a < b), 0 (a == b), 1 (a > b)
            $cmp = $a[$field] <=> $b[$field];

            // 如果是降序排序，反转比较结果
            if (!$ascending) {
                $cmp = -$cmp;
            }

            return $cmp;
        });

        return $array;
    }
}