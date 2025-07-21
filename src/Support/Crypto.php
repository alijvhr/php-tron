<?php
namespace Tron\Support;

class Crypto
{
    public static function bc2bin($num): string
    {
        return self::dec2base($num, 256);
    }

    public static function dec2base($dec, $base, $digits = false): string
    {
        if (extension_loaded('bcmath')) {
            if ($base < 2 || $base > 256) {
                die("Invalid Base: " . $base);
            }
            bcscale(0);
            $value = "";
            if (!$digits) {
                $digits = self::digits($base);
            }
            while ($dec > $base - 1) {
                $rest = bcmod($dec, $base);
                $dec = bcdiv($dec, $base);
                $value = $digits[$rest] . $value;
            }
            return $digits[intval($dec)] . $value;
        } else {
            die('Please install BCMATH');
        }
    }

    public static function base2dec($value, $base, $digits = false): string
    {
        if (extension_loaded('bcmath')) {
            if ($base < 2 || $base > 256) {
                die("Invalid Base: " . $base);
            }
            bcscale(0);
            if ($base < 37) {
                $value = strtolower($value);
            }
            if (!$digits) {
                $digits = self::digits($base);
            }
            $size = strlen($value);
            $dec = "0";
            for ($loop = 0; $loop < $size; $loop++) {
                $element = strpos($digits, (string) $value[$loop]);
                $power = bcpow($base, $size - $loop - 1);
                $dec = bcadd($dec, bcmul($element, $power));
            }
            return $dec;
        } else {
            die('Please install BCMATH');
        }
    }

    public static function digits($base): string
    {
        if ($base > 64) {
            $digits = "";
            for ($loop = 0; $loop < 256; $loop++) {
                $digits .= chr($loop);
            }
        } else {
            $digits = "0123456789abcdefghijklmnopqrstuvwxyz";
            $digits .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ-_";
        }
        return substr($digits, 0, $base);
    }

    public static function bin2bc($num): string
    {
        return self::base2dec($num, 256);
    }
}
