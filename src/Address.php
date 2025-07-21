<?php

namespace Tron;

use InvalidArgumentException;
use Tron\Support\Base58Check;
use Tron\Support\Hash;

class Address
{
    use TronAwareTrait;
    public string $address;
    public string $hexAddress = '';

    const ADDRESS_SIZE = 34;
    const ADDRESS_PREFIX = "41";
    const ADDRESS_PREFIX_BYTE = 0x41;

    public function __construct(string $address = '', public string $privateKey = '', string $hexAddress = '')
    {
        if ($address === '') {
            throw new InvalidArgumentException('Address can not be empty');
        }
        $this->address = $address;
        $this->hexAddress = $hexAddress ?: $this->address2HexString($address);
    }

    /**
     * Dont rely on this. Always use Wallet::validateAddress to double check
     * against tronGrid.
     */
    public function isValid(): bool
    {
        if (strlen($this->address) !== Address::ADDRESS_SIZE) {
            return false;
        }

        $address = Base58Check::decode($this->address, false, 0, false);
        $utf8 = hex2bin($address);

        if (strlen($utf8) !== 25) {
            return false;
        }

        if (!str_starts_with($utf8, chr(self::ADDRESS_PREFIX_BYTE))) {
            return false;
        }

        $checkSum = substr($utf8, 21);
        $address = substr($utf8, 0, 21);

        $hash0 = Hash::SHA256($address);
        $hash1 = Hash::SHA256($hash0);
        $checkSum1 = substr($hash1, 0, 4);
        return $checkSum === $checkSum1;
    }
}
