<?php

namespace Tron;

use Exception;
use Tron\Exception\TronException;
use Tron\Provider\HttpProvider;
use InvalidArgumentException;
use Phactor\Key;
use Tron\Exceptions\TransactionException;
use Tron\Exceptions\TronErrorException;
use Tron\Interfaces\WalletInterface;
use Tron\Support\Key as SupportKey;

class TRX implements WalletInterface
{
    public Address $contractAddress;

    public Tron $tron;

    public function __construct(protected Api $_api, array $config = [])
    {
        $host = $_api->getClient()->getConfig('base_uri')->getScheme() . '://' . $_api->getClient()->getConfig('base_uri')->getHost();
        $fullNode = new HttpProvider($host);
        $solidityNode = new HttpProvider($host);
        $eventServer = new HttpProvider($host);
        try {
            $this->tron = new Tron($fullNode, $solidityNode, $eventServer);
        } catch (TronException $e) {
            throw new TronErrorException($e->getMessage(), $e->getCode());
        }
    }

    public function generateAddress(): Address
    {
        $attempts = 0;
        $validAddress = false;

        do {
            if ($attempts++ === 5) {
                throw new TronErrorException('Could not generate valid key');
            }

            $key = new Key([
                'private_key_hex' => '',
                'private_key_dec' => '',
                'public_key' => '',
                'public_key_compressed' => '',
                'public_key_x' => '',
                'public_key_y' => ''
            ]);
            $keyPair = $key->GenerateKeypair();
            $privateKeyHex = $keyPair['private_key_hex'];
            $pubKeyHex = $keyPair['public_key'];

            //We cant use hex2bin unless the string length is even.
            if (strlen($pubKeyHex) % 2 !== 0) {
                continue;
            }

            try {
                $addressHex = Address::ADDRESS_PREFIX . SupportKey::publicKeyToAddress($pubKeyHex);
                $addressBase58 = SupportKey::getBase58CheckAddress($addressHex);
            } catch (InvalidArgumentException $e) {
                throw new TronErrorException($e->getMessage());
            }
            $address = new Address($addressBase58, $privateKeyHex, $addressHex);
            $validAddress = $this->validateAddress($address);
        } while (!$validAddress);

        return $address;
    }

    public function validateAddress(Address $address): bool
    {
        if (!$address->isValid()) {
            return false;
        }

        $body = $this->_api->post('/wallet/validateaddress', [
            'address' => $address->address,
        ]);

        return $body->result;
    }

    public function privateKeyToAddress(string $privateKeyHex): Address
    {
        try {
            $addressHex = Address::ADDRESS_PREFIX . SupportKey::privateKeyToAddress($privateKeyHex);
            $addressBase58 = SupportKey::getBase58CheckAddress($addressHex);
        } catch (InvalidArgumentException $e) {
            throw new TronErrorException($e->getMessage());
        }
        $address = new Address($addressBase58, $privateKeyHex, $addressHex);
        $validAddress = $this->validateAddress($address);
        if (!$validAddress) {
            throw new TronErrorException('Invalid private key');
        }

        return $address;
    }

    public function resource(Address $address): array
    {
        return $this->tron->getAccountResources($address->address);
    }

    public function balance(Address $address): float
    {
        $this->tron->setAddress($address->address);
        return $this->tron->getBalance(null, true);
    }

    public function transfer(Address $from, Address $to, float $amount): Transaction
    {
        $this->tron->setAddress($from->address);
        $this->tron->setPrivateKey($from->privateKey);

        try {
            $transaction = $this->tron->getTransactionBuilder()->sendTrx($to->address, $amount, $from->address);
            $signedTransaction = $this->tron->signTransaction($transaction);
            $response = $this->tron->sendRawTransaction($signedTransaction);
        } catch (TronException $e) {
            throw new TransactionException($e->getMessage(), $e->getCode());
        }

        if (isset($response['result']) && $response['result']) {
            return new Transaction(
                $transaction['txID'],
                $transaction['raw_data'],
                'PACKING'
            );
        }

        throw new TransactionException(hex2bin($response['message']));
    }

    /**
     * @throws TransactionException
     */
    public function blockNumber(): Block
    {
        try {
            $block = $this->tron->getCurrentBlock();
        } catch (TronException $e) {
            throw new TransactionException($e->getMessage(), $e->getCode());
        }
        $transactions = $block['transactions'] ?? [];
        return new Block($block['blockID'], $block['block_header'], $transactions);
    }

    /**
     * @throws TransactionException
     * @throws Exception
     */
    public function blockByNumber(int $blockID): Block
    {
        try {
            $block = $this->tron->getBlockByNumber($blockID);
        } catch (TronException $e) {
            throw new TransactionException($e->getMessage(), $e->getCode());
        }

        $transactions = $block['transactions'] ?? [];
        return new Block($block['blockID'], $block['block_header'], $transactions);
    }

    /**
     * @throws TransactionException
     */
    public function transactionReceipt(string $txHash): Transaction
    {
        try {
            $detail = $this->tron->getTransaction($txHash);
        } catch (TronException $e) {
            throw new TransactionException($e->getMessage(), $e->getCode());
        }
        return new Transaction(
            $detail['txID'],
            $detail['raw_data'],
            $detail['ret'][0]['contractRet'] ?? ''
        );
    }

    public function walletTransactions(Address $address, ?int $limit = null, string $fingerprint = '', bool $only_confirmed = true): ?array
    {
        if (!$address->isValid()) {
            return null;
        }

        $trc20contractAddress = $this->contractAddress?->address ?? '';
        $trc20 = $trc20contractAddress !== '' && $trc20contractAddress !== '0' ? '/trc20' : '';

        $body = $this->_api->get("/v1/accounts/$address->address/transactions$trc20", [
            'contract_address' => $trc20contractAddress,
            'limit' => $limit,
            'fingerprint' => $fingerprint,
            'only_confirmed' => $only_confirmed,
        ]);

        return $body->data;
    }
}
