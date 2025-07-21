<?php declare(strict_types=1);

namespace Tron;

use Tron\Support\{Base58Check, BigInteger, Keccak};
use Exception;

trait TronAwareTrait
{
    /**
     * Convert from Hex
     *
     * @param $string
     */
    public function fromHex($string): string
    {
        if(strlen($string) == 42 && mb_substr($string,0,2) === '41') {
            return $this->hexString2Address($string);
        }

        return $this->hexString2Utf8($string);
    }

    /**
     * Convert to Hex
     *
     * @param $str
     */
    public function toHex($str): string
    {
        if(mb_strlen($str) == 34 && mb_substr($str, 0, 1) === 'T') {
            return $this->address2HexString($str);
        };

        return $this->stringUtf8toHex($str);
    }

    /**
     * Check the address before converting to Hex
     *
     * @param $sHexAddress
     */
    public function address2HexString($sHexAddress): string
    {
        if(strlen($sHexAddress) == 42 && mb_strpos($sHexAddress, '41') == 0) {
            return $sHexAddress;
        }
        return Base58Check::decode($sHexAddress,0,3);
    }

    /**
     * Check Hex address before converting to Base58
     *
     * @param $sHexString
     */
    public function hexString2Address($sHexString): string
    {
        if(!ctype_xdigit($sHexString)) {
            return $sHexString;
        }

        if(strlen($sHexString) < 2 || (strlen($sHexString) & 1) != 0) {
            return '';
        }

        return Base58Check::encode($sHexString,0,false);
    }

    /**
     * Convert string to hex
     *
     * @param $sUtf8
     */
    public function stringUtf8toHex($sUtf8): string
    {
        return bin2hex($sUtf8);
    }

    /**
     * Convert hex to string
     *
     * @param $sHexString
     * @return string
     */
    public function hexString2Utf8($sHexString): string|false
    {
        return hex2bin($sHexString);
    }

    /**
     * Convert to great value
     *
     * @param $str
     */
    public function toBigNumber($str): \Tron\Support\BigInteger {
        return new BigInteger($str);
    }

    /**
     * Convert trx to float
     *
     * @param $amount
     */
    public function fromTron($amount): float {
        return (float) bcdiv((string)$amount, (string)1e6, 8);
    }

    /**
     * Convert float to trx format
     *
     * @param $double
     */
    public function toTron($double): int {
        return (int) bcmul((string)$double, (string)1e6,0);
    }

    /**
     * Convert to SHA3
     *
     * @param $string
     * @throws Exception
     */
    public function sha3($string, bool $prefix = true): string
    {
        return ($prefix ? '0x' : ''). Keccak::hash($string, 256);
    }
}