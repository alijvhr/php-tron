<?php
namespace Tron;

use Tron\Exception\TronException;

class TronAddress
{
    /**
     * Конструктор
     * @throws TronException
     */
    public function __construct(/**
     * Результаты генерации адресов
     */
    protected array $response)
    {
        // Проверяем ключи, перед выводом результатов
        if(!$this->array_keys_exist($this->response, ['address_hex', 'private_key', 'public_key'])) {
            throw new TronException('Incorrectly generated address');
        }
    }

    /**
     * Получение адреса
     */
    public function getAddress(bool $is_base58 = false): string
    {
        return $this->response[($is_base58 == false) ? 'address_hex' : 'address_base58'];
    }

    /**
     * Получение публичного ключа
     */
    public function getPublicKey(): string
    {
        return $this->response['public_key'];
    }

    /**
     * Получение приватного ключа
     */
    public function getPrivateKey(): string
    {
        return $this->response['private_key'];
    }

    /**
     * Получение результатов в массике
     */
    public function getRawData(): array
    {
        return $this->response;
    }

    /**
     * Проверка нескольких ключей
     */
    private function array_keys_exist(array $array, array $keys = []): bool
    {
        $count = 0;
        if (!is_array($keys)) {
            $keys = func_get_args();
            array_shift($keys);
        }
        foreach ($keys as $key) {
            if (isset( $array[$key]) || array_key_exists($key, $array)) {
                $count ++;
            }
        }

        return count($keys) === $count;
    }
}