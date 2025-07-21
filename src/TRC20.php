<?php

namespace Tron;

use Tron\Exception\TronException;
use InvalidArgumentException;
use Tron\Exceptions\TransactionException;
use Tron\Exceptions\TronErrorException;
use Tron\Support\Formatter;
use Tron\Support\Utils;

class TRC20 extends TRX
{
    public Address $contractAddress;

    protected int $decimals;

    /**
     * @throws TronErrorException
     */
    public function __construct(Api $_api, array $config)
    {
        parent::__construct($_api, $config);

        $this->contractAddress = new Address(
            $config['contract_address'],
            '',
            $this->tron->address2HexString($config['contract_address'])
        );
        $this->decimals = $config['decimals'];
    }

    /**
     * @throws TronErrorException
     */
    public function balance(Address $address): string
    {
        $format = Formatter::toAddressFormat($address->hexAddress);
        $body = $this->_api->post('/wallet/triggersmartcontract', [
            'contract_address'  => $this->contractAddress->hexAddress,
            'function_selector' => 'balanceOf(address)',
            'parameter'         => $format,
            'owner_address'     => $address->hexAddress,
        ]);

        if (isset($body->result->code)) {
            throw new TronErrorException(hex2bin($body->result->message));
        }

        try {
            $balance = Utils::toDisplayAmount(hexdec($body->constant_result[0]), $this->decimals);
        } catch (InvalidArgumentException $e) {
            throw new TronErrorException($e->getMessage());
        }
        return $balance;
    }

    /**
     * @throws TransactionException
     * @throws TronErrorException
     */
    public function transfer(Address $from, Address $to, float $amount): Transaction
    {
        $this->tron->setAddress($from->address);
        $this->tron->setPrivateKey($from->privateKey);

        $toFormat = Formatter::toAddressFormat($to->hexAddress);
        try {
            $amount = Utils::toMinUnitByDecimals($amount, $this->decimals);
        } catch (InvalidArgumentException $e) {
            throw new TronErrorException($e->getMessage());
        }
        $numberFormat = Formatter::toIntegerFormat($amount);

        $body = $this->_api->post('/wallet/triggersmartcontract', [
            'contract_address'  => $this->contractAddress->hexAddress,
            'function_selector' => 'transfer(address,uint256)',
            'parameter'         => "$toFormat$numberFormat",
            'fee_limit'         => 100000000,
            'call_value'        => 0,
            'owner_address'     => $from->hexAddress,
        ], true);

        if (isset($body['result']['code'])) {
            throw new TransactionException(hex2bin($body['result']['message']));
        }

        try {
            $tradeobj = $this->tron->signTransaction($body['transaction']);
            $response = $this->tron->sendRawTransaction($tradeobj);
        } catch (TronException $e) {
            throw new TransactionException($e->getMessage(), $e->getCode());
        }

        return isset($response['result']) && $response['result'] ? new Transaction(
            $body['transaction']['txID'],
            $body['transaction']['raw_data'],
            'PACKING'
        ) : throw new TransactionException(hex2bin($response['result']['message']));

    }
}
