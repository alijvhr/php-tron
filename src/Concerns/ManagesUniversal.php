<?php
namespace Tron\Concerns;


use Tron\Exception\ErrorException;

trait ManagesUniversal
{
    /**
     * Default Attributes
     */
    private array $attribute = [
        'balances'  =>  [],
        'one_to_many' => []
    ];

    /**
     * Check multiple balances
     *
     * @throws ErrorException
     */
    public function balances(array $accounts, bool $isValid = false): array
    {
        if(!is_array($accounts)) {
            throw new ErrorException('Data must be an array');
        }

        if(count($accounts) > 20) {
            throw new ErrorException('Once you can check 20 accounts');
        }

        foreach ($accounts as $item)
        {
            if($isValid && $this->validateAddress($item[0])['result'] == false) {
                throw new ErrorException($item[0].' invalid address');
            }

            $this->attribute['balances'][] = [
                'address'   =>  $item[0],
                'balance'   =>  $this->getBalance($item[0], $item[1])
            ];
        }

        return $this->attribute['balances'];
    }

    /**
     * We send funds to several addresses at once.
     *
     * @param null $private_key
     * @throws ErrorException
     */
    public function sendOneToMany(array $to, $private_key = null, bool $isValid = false, string $from = null): array
    {
        if(!is_null($private_key)) {
            $this->privateKey = $private_key;
        }

        if(count($to) > 10) {
            throw new ErrorException('Allowed to send to "10" accounts');
        }

        foreach ($to as $item)
        {
            if($isValid && $this->validateAddress($item[0])['result'] == false) {
                throw new ErrorException($item[0].' invalid address');
            }

            $this->attribute['one_to_many'][] = $this->send($item[0], $item[1], $from);
        }

        return $this->attribute['one_to_many'];
    }
}
