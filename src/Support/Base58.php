<?php
namespace Tron\Support;

class Base58
{
    /**
     * Encodes the passed whole string to base58.
     *
     * @param $num
     */
    public static function encode($num, int $length = 58): string
    {
        return Crypto::dec2base($num, $length, '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz');
    }

    /**
     * Base58 decodes a large integer to a string.
     *
     *
     */
    public static function decode(string $addr, int $length = 58): string
    {
        return Crypto::base2dec($addr, $length, '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz');
    }
}
