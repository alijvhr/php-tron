<?php

namespace Tron\Support;

/**
 * 数据签名
 * Class Formatter
 * @package Ethereum
 */
class Formatter
{

    /**
     * 对于方法名和参数类型做签名
     * @param $method
     */
    public static function toMethodFormat(string $method): string
    {
        return Utils::stripZero(substr(Utils::sha3($method), 0, 10));
    }

    /**
     * 地址签名
     * @param $address
     */
    public static function toAddressFormat($address): string
    {
        if (Utils::isAddress($address)) {
            $address = strtolower($address);

            if (Utils::isZeroPrefixed($address)) {
                $address = Utils::stripZero($address);
            }
        }
        return implode('', array_fill(0, 64 - strlen($address), 0)) . $address;
    }

    /**
     * 数字签名
     * @param $value
     */
    public static function toIntegerFormat($value, int $digit = 64): string
    {
        $bn = Utils::toBn($value);
        $bnHex = $bn->toHex(true);
        $padded = mb_substr($bnHex, 0, 1);

        if ($padded !== 'f') {
            $padded = '0';
        }
        return implode('', array_fill(0, $digit - mb_strlen($bnHex), $padded)) . $bnHex;
    }
}
