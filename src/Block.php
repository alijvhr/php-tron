<?php

namespace Tron;

use Exception;

class Block
{
    public string $blockID;

    public function __construct(string $blockID, public array $block_header, public array $transactions = [])
    {
        if ($blockID === '') {
            throw new Exception('blockID empty');
        }

        $this->blockID = $blockID;
    }
}
