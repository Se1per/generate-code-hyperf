<?php

namespace Japool\Genconsole\Help\src;

trait ArrayTrait
{
    /**
     * 二维数组去重
     * @param $array $二维数组
     * @param $field $根据二维数组中的某个字段进行去重
     * @return array|false
     */
    public function arrayUnique($array, $field)
    {
        if (empty($array) || !$field) {
            return false;
        }

        //返回指定字段的一列数据，并去重
        $fields = array_unique(array_column($array, $field));

        //使用 array_filter 过滤数组中不符合条件的数据
        $data = array_filter($array, function ($item) use ($field, $fields) {
            return in_array($item[$field], $fields);
        });

        //重置数组的索引
        return array_values($data);
    }

    /**
     * 分页兑换参数
     * @param array $data
     * @return array
     */
    public function pageLimit(array $data): array
    {
        $data['page'] = $data['page'] ?? 1;

        $data['pagesize'] = $data['pagesize'] ?? 10;

        $data['page'] = ($data['page'] - 1) * $data['pagesize'];

        return $data;
    }

    /**
     *  指定根层级的树状图
     * @param array $list 初始数组
     * @param int $root 最上级一条数据的id
     * @param string $pk 每一条数据的id
     * @param string $pid 上下级关系的pid
     * @param string $child 自定义下级关系的字段
     * @return  array $tree  树状图数组
     */
    public function generateTree(array $list, int $root = 0, string $pk = 'id', string $pid = 'pid', string $child = 'child'): array
    {
        $tree = array();
        $packData = array();

        foreach ($list as $data) {
            $packData[$data[$pk]] = $data;
        }

        foreach ($packData as $key => $val) {
            if ($val[$pid] == $root) {
                //代表跟节点, 重点一
                $tree[] = &$packData[$key];
            } else {
                //找到其父类,重点二
                $packData[$val[$pid]][$child][] = &$packData[$key];
            }
        }
        return $tree;
    }

    
    /**
     *  指定根层级的树状图 (树状协程)
     * @param array $list 初始数组
     * @param int $root 最上级一条数据的id
     * @param string $pk 每一条数据的id
     * @param string $pid 上下级关系的pid
     * @param string $child 自定义下级关系的字段
     * @return  array $tree  树状图数组
     */
    public function generateTree1(array $list, int $root = 0, string $pk = 'id', string $pid = 'pid', string $child = 'child'): array
    {
        $tree = [];
        $packData = [];

        $concurrent = new Concurrent(30);

        foreach ($list as $data) {
            $concurrent->create(function () use ($pk,$data,&$packData) {
                $packData[$data[$pk]] = $data;
            });
        }

        foreach ($packData as $key => $val) {
            $concurrent->create(function () use ($packData,$key,&$val,$pid,$child,&$tree) {
                if (isset($packData[$val[$pid]])) {
                    if (!isset($packData[$val[$pid]][$child])) {
                        $packData[$val[$pid]][$child] = [];
                    }
                    $packData[$val[$pid]][$child][] = &$val;
                } else {
                    $tree[] = &$val;
                }
            });
        }

        return $tree;
    }

    public function sortByByteArray($array, $pIndex, $index)
    {
        usort($array, function ($a, $b) use ($index, $pIndex) {
//            $valueA = is_array($a[$pIndex][$index]) ? implode('', $a[$pIndex][$index]) : $a[$pIndex][$index];
//            $valueB = is_array($b[$pIndex][$index]) ? implode('', $b[$pIndex][$index]) : $b[$pIndex][$index];
            $valueA = is_array($a[$pIndex]) ? $a[$pIndex][$index] : 0;
            $valueB = is_array($b[$pIndex]) ? $b[$pIndex][$index] : 0;
            return $valueB - $valueA;
        });

        return $array;
    }

    /**
     * 二维数组排序
     * @param $array
     * @param $field
     * @param $ascending
     * @return mixed
     * User: Se1per
     * Date: 2023/9/6 10:11
     */
    public function sortByField($array, $field, $ascending = true)
    {
        usort($array, function ($a, $b) use ($field, $ascending) {
            // 根据字段的值进行比较
            $cmp = $a[$field] <=> $b[$field];

            // 如果需要降序排序，则反转比较结果
            if (!$ascending) {
                $cmp = -$cmp;
            }

            return $cmp;
        });

        return $array;
    }
}