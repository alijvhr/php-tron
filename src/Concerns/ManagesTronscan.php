<?php
namespace Tron\Concerns;


use Tron\Exception\TronException;

trait ManagesTronscan
{
    /**
     * Transactions from explorer
     *
     * @throws TronException
     */
    public function getTransactionByAddress(array $options = []): array
    {
        if($options === []) {
            throw new TronException('Parameters must not be empty.');
        }

        return $this->manager->request('api/transaction', $options);
    }
}