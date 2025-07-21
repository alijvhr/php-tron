<?php

namespace Tron;

class Transaction
{
    public array $signature = [];

    public function __construct(public string $txID, public array $raw_data, public string $contractRet)
    {
    }

    public function isSigned(): bool
    {
        return (bool)count($this->signature);
    }
}
