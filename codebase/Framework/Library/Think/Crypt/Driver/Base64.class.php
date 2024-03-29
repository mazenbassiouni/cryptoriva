<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------
namespace Think\Crypt\Driver;
/**
 * Base64 Encryption implementation class
 */
class Base64
{

    /**
     * Encrypted string
     * @param string $str String
     * @param string $key encryptionkey
     * @param integer $expire Period (s)
     * @return string
     */
    public static function encrypt($data, $key, $expire = 0)
    {
        $char="";
        $str="";
        $expire = sprintf('%010d', $expire ? $expire + time() : 0);
        $key = md5($key);
        $data = base64_encode($expire . $data);
        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }

        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
        }
        return $str;
    }

    /**
     * Decryption string
     * @param string $str String
     * @param string $key encryptionkey
     * @return string
     */
    public static function decrypt($data, $key)
    {
        $char="";
        $str="";
        $key = md5($key);
        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
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
        $expire = substr($data, 0, 10);
        if ($expire > 0 && $expire < time()) {
            return '';
        }
        $data = substr($data, 10);
        return $data;
    }
}