<?php
namespace Tron\Support;

class Hash
{
    /**
     * Hashing SHA-256
     *
     * @param $data
     */
    public static function SHA256($data, bool $raw = true): string
    {
        return hash('sha256', $data, $raw);
    }

    /**
     * Double hashing SHA-256
     *
     * @param $data
     */
    public static function sha256d($data): string
    {
        return hash('sha256', hash('sha256', $data, true), true);
    }

    /**
     * Hashing RIPEMD160
     *
     * @param $data
     */
    public static function RIPEMD160($data, bool $raw = true): string
    {
        return hash('ripemd160', $data, $raw);
    }
}
