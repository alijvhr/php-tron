<?php

namespace Tron\Support;

use Exception;
use InvalidArgumentException;
use kornrunner\Keccak;
use phpseclib\Math\BigInteger;
use ValueError;

class Utils
{
    public const SHA3_NULL_HASH = 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470';

    public static function isValidUrl(string $url): bool
    {
        return (bool)parse_url($url);
    }

    public static function isArray(mixed $array): bool
    {
        return is_array($array);
    }

    public static function validate(string $address): bool
    {
        $decoded = Base58::decode($address);
        $d1 = hash('sha256', substr($decoded, 0, 21), true);
        $d2 = hash('sha256', $d1, true);

        if (strcmp(substr($decoded, 21, 4), $d2) !== 0) {
            throw new Exception('bad digest');
        }
        return true;
    }

    public static function decodeBase58(string $input): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $out = array_fill(0, 25, 0);

        for ($i = 0, $iMax = strlen($input); $i < $iMax; $i++) {
            $p = strpos($alphabet, $input[$i]);
            if ($p === false) {
                throw new Exception('invalid character found');
            }
            $c = $p;
            for ($j = 25; $j--;) {
                $c += 58 * $out[$j];
                $out[$j] = $c % 256;
                $c = (int)($c / 256);
            }
            if ($c !== 0) {
                throw new Exception('address too long');
            }
        }

        return implode('', array_map('chr', $out));
    }

    public static function pubKeyToAddress(string $pubkey): string
    {
        $bin = hex2bin($pubkey);
        if ($bin === false) {
            throw new ValueError('Invalid hex string');
        }
        return '41' . substr(Keccak::hash(substr($bin, 1), 256), 24);
    }

    public static function hasHexPrefix(string $str): bool
    {
        return str_starts_with($str, '0x');
    }

    public static function removeHexPrefix(string $str): string
    {
        return self::hasHexPrefix($str) ? substr($str, 2) : $str;
    }

    public static function toHex(mixed $value, bool $isPrefix = false): string
    {
        if (is_numeric($value)) {
            $bn = self::toBn($value);
            $hex = $bn->toHex(true);
            $hex = preg_replace('/^0+(?!$)/', '', $hex);
        } elseif (is_string($value)) {
            $value = self::stripZero($value);
            $hex = bin2hex($value);
        } elseif ($value instanceof BigInteger) {
            $hex = $value->toHex(true);
            $hex = preg_replace('/^0+(?!$)/', '', $hex);
        } else {
            throw new InvalidArgumentException('The value to toHex function is not supported.');
        }
        return $isPrefix ? '0x' . $hex : $hex;
    }

    public static function hexToBin(string $value): string
    {
        if (self::isZeroPrefixed($value)) {
            $value = str_replace('0x', '', $value);
        }
        $result = hex2bin($value);
        if ($result === false) {
            throw new ValueError('Invalid hex string');
        }
        return $result;
    }

    public static function isZeroPrefixed(string $value): bool
    {
        return str_starts_with($value, '0x');
    }

    public static function stripZero(string $value): string
    {
        return self::isZeroPrefixed($value) ? substr($value, 2) : $value;
    }

    public static function isNegative(string $value): bool
    {
        return str_starts_with($value, '-');
    }

    public static function isAddress(string $value): bool
    {
        if (preg_match('/^(0x|0X)?[a-f0-9A-F]{40}$/', $value) !== 1) {
            return false;
        }
        return preg_match('/^(0x|0X)?[a-f0-9]{40}$/', $value) === 1
            || preg_match('/^(0x|0X)?[A-F0-9]{40}$/', $value) === 1
            || self::isAddressChecksum($value);
    }

    public static function isAddressChecksum(string $value): bool
    {
        $value = self::stripZero($value);
        $hash = self::stripZero(self::sha3(mb_strtolower($value)));

        for ($i = 0; $i < 40; $i++) {
            $hashVal = intval($hash[$i], 16);
            $char = $value[$i];
            if (
                ($hashVal > 7 && !ctype_upper($char)) ||
                ($hashVal <= 7 && !ctype_lower($char))
            ) {
                return false;
            }
        }
        return true;
    }

    public static function isHex(string $value): bool
    {
        return (bool)preg_match('/^(0x)?[a-f0-9A-F]*$/', $value);
    }

    public static function sha3(string $value): ?string
    {
        if (str_starts_with($value, '0x')) {
            $value = self::hexToBin($value);
        }
        $hash = Keccak::hash($value, 256);
        return $hash === self::SHA3_NULL_HASH ? null : $hash;
    }

    public static function toBn(mixed $number): BigInteger|array
    {
        if ($number instanceof BigInteger) {
            return $number;
        }

        if (is_int($number)) {
            return new BigInteger($number);
        }

        if (is_numeric($number)) {
            $negative = self::isNegative($number);
            $number = (string)abs((float)$number);

            if (str_contains($number, '.')) {
                $parts = explode('.', $number, 2);
                if (count($parts) > 2) {
                    throw new InvalidArgumentException('toBn number must have at most one decimal point');
                }

                return [
                    new BigInteger($parts[0]),
                    new BigInteger($parts[1]),
                    strlen($parts[1]),
                    $negative ? new BigInteger(-1) : false,
                ];
            }

            $bn = new BigInteger($number);
            return $negative ? $bn->multiply(new BigInteger(-1)) : $bn;
        }

        if (is_string($number)) {
            $negative = self::isNegative($number);
            $number = $negative ? substr($number, 1) : $number;

            if (self::isZeroPrefixed($number) || preg_match('/[a-f]+/', $number)) {
                $number = self::stripZero($number);
                $bn = new BigInteger($number, 16);
                return $negative ? $bn->multiply(new BigInteger(-1)) : $bn;
            }

            if ($number === '') {
                return new BigInteger(0);
            }

            throw new InvalidArgumentException('Invalid hex string');
        }

        throw new InvalidArgumentException('Invalid input type for toBn');
    }

    public static function toDisplayAmount(string|int|float $number, int $decimals): string
    {
        $number = number_format((float)$number, 0, '.', '');
        $bn = self::toBn($number);
        $bnt = self::toBn(10 ** $decimals);
        return self::divideDisplay($bn->divide($bnt), $decimals);
    }

    public static function divideDisplay(array $divResult, int $decimals): string
    {
        [$bnq, $bnr] = $divResult;
        $ret = (string)$bnq->value;
        if ($bnr->value > 0) {
            $ret .= '.' . rtrim(sprintf("%0{$decimals}d", $bnr->value), '0');
        }
        return $ret;
    }

    public static function toMinUnitByDecimals(mixed $number, int $decimals): BigInteger
    {
        $bn = self::toBn($number);
        $bnt = self::toBn(10 ** $decimals);

        if (is_array($bn)) {
            [$whole, $fraction, $fractionLength, $negative] = $bn;
            $whole = $whole->multiply($bnt);

            $base = match (MATH_BIGINTEGER_MODE) {
                $whole::MODE_GMP    => new BigInteger(gmp_pow(10, $fractionLength)),
                $whole::MODE_BCMATH => new BigInteger(bcpow('10', (string)$fractionLength)),
                default             => new BigInteger(10 ** $fractionLength),
            };

            $fraction = $fraction->multiply($bnt)->divide($base)[0];
            $result = $whole->add($fraction);
            return $negative ? $result->multiply($negative) : $result;
        }

        return $bn->multiply($bnt);
    }
}