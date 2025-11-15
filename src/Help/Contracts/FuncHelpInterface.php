<?php

namespace Japool\Genconsole\Help\Contracts;

/**
 * 工具函数包接口
 * 定义所有可用功能的方法签名和使用说明
 * 
 * 此接口基于现有的 FuncHelp 类，包含以下功能模块：
 * - AesTrait: AES 加密解密功能
 * - ArrayTrait: 数组处理功能
 * - DateTimeTrait: 时间日期处理功能
 * - GeographyTrait: 地理数据处理功能
 * - StringTrait: 字符串处理功能
 * - XmlTrait: XML 处理功能
 */
interface FuncHelpInterface
{
    // ==================== AES 加密解密功能 ====================

    /**
     * 生成随机密钥
     * @return string 32位十六进制密钥
     * 
     * @example
     * $key = $help->makeKey();
     * // 结果: "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6"
     */
    public function makeKey(): string;

    /**
     * AES 加密
     * @param mixed $data 待加密数据（支持数组自动转JSON）
     * @param int $expire 过期时间（秒），0表示永不过期
     * @param string|null $key 加密密钥，null时使用默认密钥
     * @return string 加密后的字符串
     * 
     * @example
     * $encrypted = $help->encrypt('sensitive data', 3600);
     * $encryptedArray = $help->encrypt(['id' => 1, 'name' => '张三'], 0);
     */
    public function encrypt($data, int $expire = 0, ?string $key = null): string;

    /**
     * AES 解密
     * @param string $data 加密字符串
     * @param string|null $key 解密密钥，null时使用默认密钥
     * @return mixed 解密后的数据（可能是字符串或数组）
     * 
     * @example
     * $decrypted = $help->decrypt($encrypted);
     * // 如果原数据是数组，会自动解析为数组
     */
    public function decrypt($data, ?string $key = null);

    // ==================== 数组处理功能 ====================

    /**
     * 二维数组按字段去重
     * @param array $array 待去重的二维数组
     * @param string $field 去重字段名
     * @return array|false 去重后的数组，失败返回 false
     * 
     * @example
     * $data = [
     *     ['id' => 1, 'name' => '张三'],
     *     ['id' => 2, 'name' => '李四'],
     *     ['id' => 1, 'name' => '张三重复']
     * ];
     * $result = $help->arrayUnique($data, 'id');
     * // 结果: [['id' => 1, 'name' => '张三'], ['id' => 2, 'name' => '李四']]
     */
    public function arrayUnique($array, $field);

    /**
     * 数组分页处理
     * @param array $data 原始数组数据
     * @return array 包含分页信息的数组
     * 
     * @example
     * $result = $help->pageLimit($data);
     */
    public function pageLimit(array $data): array;

    /**
     * 生成树状结构
     * @param array $list 原始数据数组
     * @param int $root 根节点ID，默认为0
     * @param string $pk 主键字段名
     * @param string $pid 父级字段名
     * @param string $child 子级字段名
     * @return array 树状结构数组
     * 
     * @example
     * $data = [
     *     ['id' => 1, 'name' => '父级', 'pid' => 0],
     *     ['id' => 2, 'name' => '子级1', 'pid' => 1],
     *     ['id' => 3, 'name' => '子级2', 'pid' => 1]
     * ];
     * $tree = $help->generateTree($data);
     * // 结果: 包含children的树状结构
     */
    public function generateTree(array $list, int $root = 0, string $pk = 'id', string $pid = 'pid', string $child = 'child'): array;

    /**
     * 按字节数组排序
     * @param array $array 要排序的数组
     * @param mixed $pIndex 父级索引
     * @param mixed $index 索引
     * @return array 排序后的数组
     */
    public function sortByByteArray($array, $pIndex, $index);

    /**
     * 按字段排序
     * @param array $array 要排序的数组
     * @param string $field 排序字段
     * @param bool $ascending 是否升序，默认true
     * @return array 排序后的数组
     * 
     * @example
     * $sorted = $help->sortByField($data, 'age', false); // 按年龄降序
     */
    public function sortByField($array, $field, $ascending = true);

    // ==================== 字符串处理功能 ====================

    /**
     * 判断字符串是否包含中文
     * @param string $str 待检测字符串
     * @return bool 是否包含中文字符
     * 
     * @example
     * $hasChinese = $help->containsChinese('hello 世界');
     * // 结果: true
     */
    public function containsChinese($str): bool;

    /**
     * 分割字符串
     * @param string $str 要分割的字符串
     * @param int $n 分割长度，默认20
     * @return array 分割后的数组
     */
    public function splitString($str, $n = 20): array;

    /**
     * 合并字符串
     * @param array $parts 字符串数组
     * @return string 合并后的字符串
     */
    public function mergeString($parts): string;

    /**
     * 创建随机字符串
     * @param int $length 长度，默认32
     * @param int $type 类型，默认1
     * @param string|null $confound 混淆字符串
     * @param int $PatchingType 补丁类型，默认0
     * @param string|null $Patching 补丁内容
     * @return string 随机字符串
     */
    public function createNonceStr(int $length = 32, int $type = 1, ?string $confound = null, int $PatchingType = 0, ?string $Patching = null): string;

    /**
     * 读取JSON文件
     * @param string $filePath 文件路径
     * @param bool $assoc 是否返回关联数组，默认true
     * @return mixed JSON数据
     */
    public function readJsonFile(string $filePath, bool $assoc = true);

    /**
     * BZ2压缩字符串
     * @param string $string 要压缩的字符串
     * @return string 压缩后的字符串
     */
    public function compressBz2String($string): string;

    /**
     * BZ2解压字符串
     * @param string $string 要解压的字符串
     * @return string 解压后的字符串
     */
    public function decompressBz2String($string): string;

    /**
     * 压缩字符串
     * @param string $string 要压缩的字符串
     * @return string 压缩后的字符串
     */
    public function compressString($string): string;

    /**
     * 解压字符串
     * @param string $string 要解压的字符串
     * @return string 解压后的字符串
     */
    public function decompressString($string): string;

    /**
     * 获取当前域名（静态方法）
     * @return string 完整的域名（包含协议）
     * 
     * @example
     * $domain = $help->getHttpType();
     * // 结果: "https://example.com"
     */
    public function getHttpType(): string;

    /**
     * 字符串替换（静态方法）
     * @param mixed $replaceStr 被替换的字符串或数组
     * @param string $search 搜索的字符串
     * @param string $replace 替换的字符串
     * @return mixed 替换后的结果
     * 
     * @example
     * $result = $help->replaceStr('hello world', 'world', 'php');
     * // 结果: "hello php"
     */
    public function replaceStr($replaceStr, $search, $replace);

    // ==================== 时间日期处理功能 ====================

    /**
     * 时间友好显示
     * @param mixed $time 时间戳或时间字符串
     * @return string 友好的时间显示
     * 
     * @example
     * $pretty = $help->pretty(time() - 3600);
     * // 结果: "1小时前"
     */
    public function pretty($time): string;

    /**
     * 判断是否为时间戳格式
     * @param mixed $timestamp 要判断的值
     * @return bool 是否为有效时间戳
     * 
     * @example
     * $isTimestamp = $help->isTimestamp(1640995200);
     * // 结果: true
     */
    public function isTimestamp($timestamp): bool;

    /**
     * 获取时间戳
     * @param int $level 级别，默认0
     * @return int 时间戳
     */
    public function getTimestamp(int $level = 0): int;

    /**
     * 验证日期格式是否有效
     * @param mixed $timestamp 时间戳或日期字符串
     * @return bool 是否有效
     */
    public function isDateFormatValid($timestamp): bool;

    /**
     * 获取年月信息
     * @param int $year 年份，0表示当前年
     * @param int $mouth 月份，0表示当前月
     * @return array 年月信息数组
     */
    public function getYearMouth(int $year = 0, int $mouth = 0): array;

    /**
     * 获取指定类型的时间范围
     * @param string $type 时间类型，如'today'、'yesterday'等
     * @return array 时间范围数组
     */
    public function getTypeTime(string $type = 'today'): array;

    /**
     * 获取某年各月的时间范围
     * @param string $year 年份
     * @return array 各月时间范围数组
     */
    public function getMonthTimes(string $year): array;

    /**
     * 获取月份范围
     * @param mixed $date 日期
     * @param bool $returnFirstDay 是否返回第一天，默认true
     * @return array 月份范围
     */
    public function getMonthRange($date, bool $returnFirstDay = true): array;

    /**
     * 判断日期是否在范围内
     * @param mixed $dateTime 要判断的日期时间
     * @param array $dateArray 日期范围数组
     * @return bool 是否在范围内
     */
    public function isDateInRange($dateTime, $dateArray): bool;

    /**
     * 获取日期范围
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @return array 日期范围数组
     */
    public function getDateRange(string $startDate, string $endDate): array;

    /**
     * 验证完整日期格式
     * @param mixed $date 日期
     * @param string $format 日期格式，默认'Y-m-d'
     * @return bool 是否有效
     */
    public function isValidDateFull($date, $format = 'Y-m-d'): bool;

    /**
     * 计算两个时间之间的人性化差异
     * @param mixed $time1 时间1
     * @param mixed $time2 时间2
     * @param array $parts 要显示的时间部分
     * @return string 人性化的时间差异
     */
    public function diffHumanBetweenTwoTimes($time1, $time2, array $parts = ['year', 'month', 'day', 'hour', 'minute', 'second']): string;

    /**
     * 计算时间差
     * @param mixed $nowTime 当前时间
     * @param mixed $outTime 对比时间
     * @return mixed 时间差
     */
    public function calculateTimeDifference($nowTime, $outTime);

    // ==================== 地理数据处理功能 ====================

    /**
     * 计算两个经纬度之间的距离
     * @param string $lng1 第一个点的经度
     * @param string $lat1 第一个点的纬度
     * @param string $lng2 第二个点的经度
     * @param string $lat2 第二个点的纬度
     * @param string $unit 距离单位（m=米, km=千米, mi=英里, ft=英尺, nm=海里）
     * @return float 两点间距离
     * 
     * @example
     * $distance = $help->getDistance('116.397428', '39.90923', '121.473701', '31.230416', 'km');
     * // 结果: 1067.5 (北京到上海的距离，单位：千米)
     */
    public function getDistance(string $lng1, string $lat1, string $lng2, string $lat2, string $unit = 'm'): float;

    // ==================== XML 处理功能 ====================

    /**
     * XML 转数组（静态方法）
     * @param string $xml XML 字符串
     * @return array 转换后的数组
     * 
     * @example
     * $xml = '<note><to>Tove</to><from>Jani</from></note>';
     * $array = $help->xmlToArr($xml);
     * // 结果: ['to' => 'Tove', 'from' => 'Jani']
     */
    public function xmlToArr(string $xml): array;

    /**
     * 数组转 XML（静态方法）
     * @param array $array 要转换的数组
     * @return string 转换后的 XML 字符串
     * @throws \Exception 当数组为空时抛出异常
     * 
     * @example
     * $array = ['to' => 'Tove', 'from' => 'Jani'];
     * $xml = $help->arrToXml($array);
     * // 结果: "<xml><to>Tove</to><from><![CDATA[Jani]]></from></xml>"
     */
    public function arrToXml(array $array): string;
}