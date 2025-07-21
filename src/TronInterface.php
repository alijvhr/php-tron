<?php declare(strict_types=1);

namespace Tron;

use Tron\Exception\TronException;

interface TronInterface
{
    /**
     * Enter the link to the manager nodes
     *
     * @param $providers
     */
    public function setManager($providers);

    /**
     * Enter your private account key
     */
    public function setPrivateKey(string $privateKey): void;

    /**
     * Enter your account address
     */
    public function setAddress(string $address) : void;

    /**
     * Getting a balance
     */
    public function getBalance( ?string $address = null): float;

    /**
     * Query transaction based on id
     *
     * @param $transactionID
     */
    public function getTransaction(string $transactionID): array;

    /**
     * Count all transactions on the network
     */
    public function getTransactionCount(): int;

    /**
     * Send transaction to Blockchain
     *
     * @param $to
     * @param $amount
     * @param $from
     *
     * @throws TronException
     */
    public function sendTransaction(string $to, float $amount, ?string $from = null): array;

    /**
     * Modify account name
     * Note: Username is allowed to edit only once.
     *
     * @param $address
     * @param $account_name
     */
    public function changeAccountName(string $account_name, ?string $address = null): array;

    /**
     * Create an account.
     * Uses an already activated account to create a new account
     *
     * @param $address
     * @param $newAccountAddress
     */
    public function registerAccount(string $address, string $newAccountAddress): array;

    /**
     * Apply to become a super representative
     */
    public function applyForSuperRepresentative(string $address, string $url): array;


    /**
     * Get block details using HashString or blockNumber
     *
     * @param null $block
     */
    public function getBlock($block = null): array;

    /**
     * Query the latest blocks
     */
    public function getLatestBlocks(int $limit = 1): array;

    /**
     * Validate Address
     */
    public function validateAddress(string $address, bool $hex = false): array;

    /**
     * Generate new address
     */
    public function generateAddress(): TronAddress;

    /**
     * Check the address before converting to Hex
     *
     * @param $sHexAddress
     */
    public function address2HexString($sHexAddress): string;
}
