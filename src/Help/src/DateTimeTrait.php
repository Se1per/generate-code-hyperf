<?php

namespace Japool\Genconsole\Help\src;


trait DateTimeTrait
{
    /** 简便转换时间
     * @param $time
     * @return false|string
     */
    public function pretty($time)
    {
        $return = '';

        if (!is_numeric($time)) {
            $time = strtotime($time);
        }

        $htime = date('H:i', $time);

        $dif = abs(time() - $time);
        if ($dif < 10) {
            $return = '刚刚';
        } else if ($dif < 3600) {
            $return = floor($dif / 60) . '分钟前';
        } else if ($dif < 10800) {
            $return = floor($dif / 3600) . '小时前';
        } else if (date('Y-m-d', $time) == date('Y-m-d')) {
            $return = '今天 ' . $htime;
        } else if (date('Y-m-d', $time) == date('Y-m-d', strtotime('-1 day'))) {
            $return = '昨天 ' . $htime;
        } else if (date('Y-m-d', $time) == date('Y-m-d', strtotime('-2 day'))) {
            $return = '前天 ' . $htime;
        } else if (date('Y', $time) == date('Y')) {
            $return = date('m-d H:i', $time);
        } else {
            $return = date('Y-m-d H:i', $time);
        }
        return $return;
    }

    /**
     * 判断是否为时间戳格式
     * @param int|string $timestamp 要判断的字符串
     * @return bool 如果是时间戳返回True,否则返回False
     */
    public function isTimestamp($timestamp): bool
    {
        $start = strtotime('1970-01-01 00:00:00');
        $end = strtotime('2099-12-31 23:59:59');
        //判断是否为时间戳
        if (!empty($timestamp) && is_numeric($timestamp) && $timestamp <= $end && $timestamp >= $start) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获得秒级/毫秒级/微秒级/纳秒级时间戳
     * @param int $level 默认0,获得秒级时间戳. 1.毫秒级时间戳; 2.微秒级时间戳; 3.纳米级时间戳
     * @return int 时间戳
     */
    public function getTimestamp(int $level = 0): int
    {
        if ($level === 0)
            return time();
        list($msc, $sec) = explode(' ', microtime());
        if ($level === 1) {
            return intval(sprintf('%.0f', (floatval($msc) + floatval($sec)) * 1000));
        } elseif ($level === 2) {
            return intval(sprintf('%.0f', (floatval($msc) + floatval($sec)) * 1000 * 1000));
        } else {
            return intval(sprintf('%.0f', (floatval($msc) + floatval($sec)) * 1000 * 1000 * 1000));
        }
    }

    /**
     * 判断时间格式
     * @param $timestamp
     * @return bool
     */
    public function isDateFormatValid($timestamp)
    {

        if (!is_integer($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        // 定义期望的日期格式
        $format = 'Y-m-d';

        // 将时间戳转换为指定的日期格式
        $convertedDate = date($format, $timestamp);

        // 使用 DateTime::createFromFormat 验证转换后的日期格式
        $dateTimeObject = DateTime::createFromFormat($format, $convertedDate);

        // 检查转换是否成功
        $isValid = $dateTimeObject && $dateTimeObject->format($format) === $convertedDate;

        return $isValid;
    }

    /** 根据年月获取时间戳
     * @param int $year
     * @param int $mouth
     * @return array
     */
    public function getYearMouth(int $year = 0, int $mouth = 0): array
    {

        if (empty($year) || empty($mouth)) {
            $now = time();
            $year = date("Y", $now);
            $mouth = date("m", $now);
        }

        $time['begin'] = date('Y-m-d H:i:s', mktime(0, 0, 0, $mouth, 1, $year));

        $time['end'] = date('Y-m-d H:i:s', mktime(23, 59, 59, ($year + 1), 0, $year));

        return $time;
    }

    /**  获取时间周期段
     * @param string $type
     * @return mixed
     */
    public function getTypeTime(string $type = 'today'): array
    {
        switch ($type) {
            case 'yesterday';
                // 获取昨日起始时间 结束时间 和 时间戳
                $data['begin_time'] = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $data['begin_day'] = date('Y-m-d H:i:s', $data['begin_time']);
                $data['end_time'] = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
                $data['end_day'] = date('Y-m-d H:i:s', $data['end_time']);
                return $data;
            case 'thisweek';
                // 获取本周起始时间 结束时间 和 时间戳
                $data['begin_time'] = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y'));
                $data['begin_day'] = date('Y-m-d H:i:s', $data['begin_time']);
                $data['end_time'] = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7, date('Y'));
                $data['end_day'] = date('Y-m-d H:i:s', $data['end_time']);
                return $data;
            case 'season';
                $season = ceil((date('n')) / 3);//当月是第几季度
                $data['begin_day'] = date('Y-m-d H:i:s', mktime(0, 0, 0, $season * 3 - 3 + 1, 1, date('Y')));
                $data['begin_time'] = mktime(0, 0, 0, $season * 3 - 3 + 1, 1, date('Y'));
                $data['end_day'] = date('Y-m-d H:i:s', mktime(23, 59, 59, $season * 3, date('t', mktime(0, 0, 0, $season * 3, 1, date("Y"))), date('Y')));
                $data['end_time'] = mktime(0, 0, 0, $season * 3 - 3 + 1, 1, date('Y'));
                return $data;
            case 'month':
                $data['begin_time'] = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $data['begin_day'] = date('Y-m-d H:i:s', $data['begin_time']);
                $data['end_time'] = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
                $data['end_day'] = date('Y-m-d H:i:s', $data['end_time']);
                return $data;
            case 'year':
                $data['begin_time'] = mktime(0, 0, 0, 1, 1, date('Y'));
                $data['begin_day'] = date('Y-m-d H:i:s', $data['begin_time']);
                $data['end_time'] = mktime(23, 59, 59, 12, 31, date('Y'));
                $data['end_day'] = date('Y-m-d H:i:s', $data['end_time']);
                return $data;
            default:
                //取今天的 起始时间
                $data['begin_time'] = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                $data['begin_day'] = date('Y-m-d H:i:s', $data['begin_time']);
                $data['end_time'] = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                $data['end_day'] = date('Y-m-d H:i:s', $data['end_time']);
                return $data;
        }
    }

    /**  获取年份的所有月份开始和结束时间 TODO 没做按年份生产 当前只有获取当年的所有月份
     * @param $year //初始年份
     * @param $num //循环生成年份
     */
    public function getMonthTimes(string $year): array
    {
        $new = [];

        for ($i = 1; $i <= 12; $i++) {
            $arr['start'] = mktime(0, 0, 0, $i, 1, $year);

            $arr['end'] = mktime(23, 59, 59, ($i + 1), 0, $year);

            $arr['start'] = date('Y-m-d H:i:s', $arr['start']);

            $arr['end'] = date('Y-m-d H:i:s', $arr['end']);

            $new[] = $arr;

            //            array_push($new,$arr);
        }

        return $new;

    }

    /** 获取指定日期所在月的开始日期与结束日期
     * @param $date
     * @param bool $returnFirstDay
     * @return false|string
     */
    public function getMonthRange($date, bool $returnFirstDay = true)
    {
        $timestamp = strtotime($date);

        if ($returnFirstDay) {
            return date('Y-m-1 00:00:00', $timestamp);
        }

        $mDays = date('t', $timestamp);
        return date('Y-m-' . $mDays . ' 23:59:59', $timestamp);
    }

    /**
     * 检查给定日期是否在指定范围内
     * @param string $dateTime 日期时间字符串，表示需要检查的日期
     * @param array $dateArray 包含开始日期和结束日期的数组
     * @return bool 如果给定日期在范围内则返回true，否则返回false
     */
    public function isDateInRange($dateTime, $dateArray)
    {
        // 将传入的日期时间字符串转为时间戳
        $dateTimeStamp = strtotime($dateTime);

        // 确保数组有两个元素
        if (count($dateArray) < 2) {
            return false; // 如果数组元素不足，返回 false
        }

        // 构建开始时间和结束时间的时间戳
        $startDate = $dateArray[0] . ' 00:00:00';
        $endDate = $dateArray[1] . ' 23:59:59';

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);

        // 检查日期是否在范围内
        return $dateTimeStamp >= $startTimestamp && $dateTimeStamp <= $endTimestamp;
    }


    /**  获取时间周期段内所有时间
     * @param string $startDate 开始时间
     * @param string $endDate 结束时间
     * @return array
     */
    public function getDateRange(string $startDate, string $endDate): array
    {
        $sTime = strtotime($startDate);
        $eTime = strtotime($endDate);

        $dateArr = [];

        while ($sTime <= $eTime) {
            $dateArr[] = date('Y-m-d', $sTime);//得到dataArr的日期数组。
            $sTime = $sTime + 86400;
        }

        return $dateArr;
    }

    public function isValidDateFull($date, $format = 'Y-m-d')
    {
        // 使用 DateTime 和格式检查
        $d = DateTime::createFromFormat($format, $date);
        if (!($d && $d->format($format) === $date)) {
            return false;
        }

        // 进一步检查利用 strtotime
        if (strtotime($date) === false) {
            return false;
        }
        return true;

    }

    /**
     * 计算两个时间的口语化差异
     *
     * @param string|int $time1 起始时间
     * @param string|int $time2 结束时间
     * @param array $parts ['year','month','day','hour','minute','second']
     * @return string
     * @throws \Exception
     */
    public function diffHumanBetweenTwoTimes($time1, $time2, array $parts = ['year', 'month', 'day', 'hour', 'minute', 'second']): string
    {
        $ts1 = $this->normalizeToTimestamp($time1);
        $ts2 = $this->normalizeToTimestamp($time2);

        if ($ts1 > $ts2) {
            [$ts1, $ts2] = [$ts2, $ts1];
        }

        // 拆分每部分
        $y1 = (int) date('Y', $ts1);
        $m1 = (int) date('n', $ts1);
        $d1 = (int) date('j', $ts1);
        $h1 = (int) date('G', $ts1);
        $i1 = (int) date('i', $ts1);
        $s1 = (int) date('s', $ts1);

        $y2 = (int) date('Y', $ts2);
        $m2 = (int) date('n', $ts2);
        $d2 = (int) date('j', $ts2);
        $h2 = (int) date('G', $ts2);
        $i2 = (int) date('i', $ts2);
        $s2 = (int) date('s', $ts2);

        $year = $y2 - $y1;
        $month = $m2 - $m1;
        $day = $d2 - $d1;
        $hour = $h2 - $h1;
        $minute = $i2 - $i1;
        $second = $s2 - $s1;

        // 修正进位
        if ($second < 0) {
            $second += 60;
            $minute--;
        }
        if ($minute < 0) {
            $minute += 60;
            $hour--;
        }
        if ($hour < 0) {
            $hour += 24;
            $day--;
        }
        if ($day < 0) {
            $m2--; // 借一个月
            if ($m2 < 1) {
                $m2 += 12;
                $y2--;
            }
            $day += cal_days_in_month(CAL_GREGORIAN, $m2, $y2);
            $month--;
        }
        if ($month < 0) {
            $month += 12;
            $year--;
        }

        $result = [];
        if (in_array('year', $parts) && $year > 0) {
            $result[] = "{$year}年";
        }
        if (in_array('month', $parts) && $month > 0) {
            $result[] = "{$month}个月";
        }
        if (in_array('day', $parts) && $day > 0) {
            $result[] = "{$day}天";
        }
        if (in_array('hour', $parts) && $hour > 0) {
            $result[] = "{$hour}小时";
        }
        if (in_array('minute', $parts) && $minute > 0) {
            $result[] = "{$minute}分钟";
        }
        if (in_array('second', $parts) && $second > 0) {
            $result[] = "{$second}秒";
        }

        return $result ? implode('', $result) : '0秒';
    }

    /**
     * 解析任意格式时间为时间戳（含时分秒）
     */
    private function normalizeToTimestamp($input): int
    {
        if (is_int($input) || ctype_digit($input)) {
            return (int) $input;
        }
        
        $timestamp = strtotime($input);
        if ($timestamp === false) {
            throw new \Exception("非法时间格式: {$input}");
        }

        return $timestamp;
    }

    /**
     * 求两个日期之间相差的天数
     * (针对1970年1月1日之后，求之前可以采用泰勒公式)
     * @param string $day1 开始时间
     * @param string $day2 结束时间
     * @return number
     */
    public
        function diffBetweenTwoDays(
        string $day1,
        string $day2
    ): int {
        // 将时间转换为时间戳
        $nowTimestamp = strtotime($day1);
        $outTimestamp = strtotime($day2);

        // 计算时间差（秒）
        $diffTime = $outTimestamp - $nowTimestamp;

        // 转换为天数并返回
        return (int) ($diffTime / 86400); // 直接返回整数天数
    }

    /**
     * 计算两个时间的差距，并根据方向返回不同的结果
     *
     * @param string $nowTime 当前时间 (格式: Y-m-d H:i:s)
     * @param string $outTime 目标时间 (格式: Y-m-d H:i:s)
     * @return array 包含时间差信息的数组
     */
    public function calculateTimeDifference($nowTime, $outTime)
    {
        // 将时间转换为时间戳
        $nowTimestamp = strtotime($nowTime);
        $outTimestamp = strtotime($outTime);

        // 计算时间差（秒）
        $diffTime = $outTimestamp - $nowTimestamp;

        // 判断时间差的方向并计算天数
        if ($diffTime < 0) {
            // 如果目标时间已过去，返回复数形式的时间差
            $diffDays = abs(bcdiv($diffTime, 86400, 2)); // 精确到小数点后两位
            $result = [
                'status' => 'past',
                'days' => -$diffDays, // 使用负数表示过去的时间
                'is_within_30_days' => abs($diffTime) <= 30 * 86400
            ];
        } else {
            // 如果目标时间还未到来，返回正整数形式的时间差
            $diffDays = bcdiv($diffTime, 86400, 0); // 取整
            $result = [
                'status' => 'future',
                'days' => (int) $diffDays, // 使用正整数表示未来的时间
                'is_within_30_days' => abs($diffTime) <= 30 * 86400
            ];
        }

        return $result;
    }

    /** 计算两个日期相隔多少年，多少月，多少天
     * @param $date1 $date1[格式如：2011-11-5]
     * @param $date2 $date2[格式如：2012-12-01]
     * @return array
     */
    public
        function diffDate(
        string $date1,
        string $date2
    ): array {

        if (strtotime($date1) > strtotime($date2)) {
            $tmp = $date2;
            $date2 = $date1;
            $date1 = $tmp;
        }

        list($Y1, $m1, $d1) = explode('-', $date1);
        list($Y2, $m2, $d2) = explode('-', $date2);

        $Y = $Y2 - $Y1;
        $m = $m2 - $m1;
        $d = $d2 - $d1;

        if ($d < 0) {
            $d += (int) date('t', strtotime("-1 month $date2"));
            $m--;
        }

        if ($m < 0) {
            $m += 12;
            $Y--;
        }
        return array('year' => $Y, 'month' => $m, 'day' => $d);
    }

    /**  根据身份证 计算岁数
     * @param string $id
     * @return false|float|int|string
     */
    public
        function getAgeByID(
        string $id
    ) {
        //过了这年的生日才算多了1周岁
        if (empty($id))
            return '';
        $date = strtotime(substr($id, 6, 8));
        //获得出生年月日的时间戳
        $today = strtotime('today');
        //获得今日的时间戳 111cn.net
        $diff = floor(($today - $date) / 86400 / 365);
        //得到两个日期相差的大体年数

        //strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
        $age = strtotime(substr($id, 6, 8) . ' +' . $diff . 'years') > $today ? ($diff + 1) : $diff;

        return $age;
    }
}