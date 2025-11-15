<?php

namespace Japool\Genconsole\Help\src;

trait AesTrait
{
    private function getKey()
    {
        return getenv('APP_NAME');
    }

    public function makeKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function getAesKey()
    {
        return '235325fdgerteGHdsfsdewred4345341';
    }

    private function getIvKey()
    {
        return 'dsfsdewred434534';
    }

    /**
     * 加密字符串  原生aes
     * @param $data
     * @param int $expire
     * @param string|null $key
     * @return string
     */
    public function encrypt($data, int $expire = 0, ?string $key = null): string
    {
//        if (is_array($data))
        $data = json_encode($data, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);

        $expire = sprintf('%010d', $expire ? $expire + time() : 0);

        if (!$key) $key = $this->getKey();

        $key = md5($key);
        $data = base64_encode($expire . $data);
        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        $char = $str = '';

        for ($i = 0; $i < $len; $i++) {
            if ($x == $l)
                $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }

        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
        }
        return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
    }

    /**
     * 解密字符串  原生aes
     * @param string $data
     * @param string|null $key
     * @return mixed
     */
    public function decrypt($data, ?string $key = null)
    {
        if (!$data) return null;

        if (!$key) $key = $this->getKey();

        $key = md5($key);
        $data = str_replace(array('-', '_'), array('+', '/'), $data);
        $mod4 = strlen($data) % 4;

        if ($mod4) {
            $data .= substr('====', $mod4);
        }

        $data = base64_decode($data);
        if ($data === false) {
            return null;
        }

        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        $char = $str = '';

        for ($i = 0; $i < $len; $i++) {
            if ($x == $l)
                $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }

        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }

        $data = base64_decode($str);
        if ($data === false) {
            return null;
        }

        //计算时间
        $expire = substr($data, 0, 10);

        if ($expire > 0 && $expire < time()) {
            return null;
        }

        $data = substr($data, 10);

        $json = json_decode($data, true);

        // json_decode 成功返回解码后的数据，失败返回 null
        // 如果原始数据是字符串（不是 JSON），json_decode 也会返回 null
        // 所以我们需要检查原始数据是否是有效的 JSON
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        // 如果 JSON 解码失败，可能原始数据就是字符串，返回原始字符串
        return $data;
    }


    /**
     * php>=7.1
     * AES-256-CBC 加密
     * @param $data
     * @return mixed|string
     */
    function encrypt_cbc($data)
    {
        $text = openssl_encrypt($data, 'AES-256-CBC', $this->getAesKey(), OPENSSL_RAW_DATA,$this->getIvKey());
        return base64_encode($text);
    }

    /**
     * php>=7.1
     * AES-256-CBC 解密
     * @param $text
     * @return string
     */
    function decrypt_cbc($text)
    {
        $decodeText = base64_decode($text);
        $data = openssl_decrypt($decodeText, 'AES-256-CBC', $this->getAesKey(), OPENSSL_RAW_DATA,$this->getIvKey());
        return $data;
    }

    /**
     * php>=7.1
     * AES-128-ECB 加密
     * @param $str
     * @param $key
     * @return string
     */
    function encrypt_ecb($str, $key)
    {
        $key = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);
        $data = openssl_encrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return strtoupper(bin2hex($data));
    }

    /**
     * php>=7.1
     * AES-128-ECB 解密
     * @param $str
     * @param $key
     * @return bool|string
     */
    function decrypt_ecb($str, $key)
    {
        $key = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);
        $data = hex2bin(strtolower($str));
        $data = openssl_decrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA, '');
        return $data;
    }
}