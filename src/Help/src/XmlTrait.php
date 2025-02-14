<?php

namespace Japool\Genconsole\Help\src;

trait XmlTrait
{
    /**
     * 示例转换格式
     * $note=<<<XML
     * <note>
     * <to>Tove</to>
     * <from>Jani</from>
     * <heading>Reminder</heading>
     * <body>Don't forget me this weekend!</body>
     * </note>
     * XML;
     *  xml 转换 array
     * @param $xml
     * @return array
     */
    public static function xmlToArr($xml): array
    {
        return (array) simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    }

    /**  arr 转换 xml
     * @param $array
     * @return string
     * @throws \exception
     */
    public static function arrToXml($array): string
    {
        if (!is_array($array) || count($array) <= 0) {
            throw new \exception("无法转为XML");
        }
        $xml = "<xml>";
        foreach ($array as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";

        return $xml;
    }
}