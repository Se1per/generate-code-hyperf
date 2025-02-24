<?php

namespace Japool\Genconsole\Help\src;


trait GeographyTrait
{
    /**
     * 求两个已知经纬度之间的距离,单位为米
     * @param string $lng1
     * @param string $lat1
     * @param string $lng2
     * @param string $lat2
     * @param string $unit
     * @return float
     * User: Se1per
     * Date: 2023/8/3 19:16
     */
    public static function getDistance(string $lng1,string $lat1,string $lng2,string $lat2,string $unit ='m')
    {
        // 将角度转为狐度
        $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);

        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;

        switch ($unit) {
            case 'km':
                $distance = $distance / 1000; // 转换为千米
                break;
            case 'mi':
                $distance = $distance / 1609.344; // 转换为英里
                break;
            case 'ft':
                $distance = $distance / 0.3048; // 转换为英尺
                break;
            case 'nm':
                $distance = $distance / 1852; // 转换为海里
                break;
            // 如果需要其他单位的转换，可以在此处添加更多的case
        }

        return $distance;
    }

    /**
     * (地球半径算法) 计算两点地理坐标之间的距离
     * @param string $longitude1  起点经度
     * @param string $latitude1   起点纬度
     * @param string $longitude2  终点经度
     * @param string $latitude2   终点纬度
     * @param int $unit  单位 1:米 2:公里
     * @param int $decimal 精度 保留小数位数
     * @return array|string
     */
    function getEarthDistance(string $longitude1,string $latitude1, string $longitude2,string $latitude2,int $unit=1,int$decimal=4):array
    {

        $EARTH_RADIUS = 6370.996; // 地球半径系数

        $PI = 3.1415926;

        $radLat1 = $latitude1 * $PI / 180.0;
        $radLat2 = $latitude2 * $PI / 180.0;

        $radLng1 = $longitude1 * $PI / 180.0;
        $radLng2 = $longitude2 * $PI /180.0;

        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        $distance = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));

        $distance = $distance * $EARTH_RADIUS * 1000;

        if($unit==2){
            $distance = $distance / 1000;
        }

        return (string) round($distance, $decimal);

    }
}