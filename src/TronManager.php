<?php
namespace Tron;


use Tron\Exception\TronException;
use Tron\Provider\{HttpProvider, HttpProviderInterface};

class TronManager
{
    /**
     * Default Nodes
     */
    protected array $defaultNodes = [
        'fullNode'      =>  'https://api.trongrid.io',
        'solidityNode'  =>  'https://api.trongrid.io',
        'eventServer'   =>  'https://api.trongrid.io',
        'explorer'      =>  'https://apilist.tronscan.org',
        'signServer'    =>  ''
    ];

    /**
     * Status Page
     */
    protected array $statusPage = [
        'fullNode'      =>  'wallet/getnowblock',
        'solidityNode'  =>  'walletsolidity/getnowblock',
        'eventServer'   =>  'healthcheck',
        'explorer'      =>  'api/system/status'
    ];

    /**
     * @param $providers
     *@throws Exception\TronException
     */
    public function __construct(/**
     * Providers
     */
        protected array $providers)
    {
        foreach ($this->providers as $key => $value)
        {
            //Do not skip the supplier is empty
            if ($value == null) {
                $this->providers[$key] = new HttpProvider(
                    $this->defaultNodes[$key]
                );
            };

            if (is_string($this->providers[$key])) {
                $this->providers[$key] = new HttpProvider($value);
            }

            if ($key == 'signServer') {
                continue;
            }

            $this->providers[$key]->setStatusPage($this->statusPage[$key]);
        }
    }

    /**
     * List of providers
     */
    public function getProviders(): array {
        return $this->providers;
    }

    /**
     * Full Node
     *
     * @throws TronException
     */
    public function fullNode() : HttpProviderInterface
    {
        if (!array_key_exists('fullNode', $this->providers)) {
            throw new TronException('Full node is not activated.');
        }

        return $this->providers['fullNode'];
    }

    /**
     * Solidity Node
     *
     * @throws TronException
     */
    public function solidityNode() : HttpProviderInterface
    {
        if (!array_key_exists('solidityNode', $this->providers)) {
            throw new TronException('Solidity node is not activated.');
        }

        return $this->providers['solidityNode'];
    }

    /**
     * Sign server
     *
     * @throws TronException
     */
    public function signServer(): HttpProviderInterface
    {
        if (!array_key_exists('signServer', $this->providers)) {
            throw new TronException('Sign server is not activated.');
        }

        return $this->providers['signServer'];
    }

    /**
     * TronScan server
     *
     * @throws TronException
     */
    public function explorer(): HttpProviderInterface
    {
        if (!array_key_exists('explorer', $this->providers)) {
            throw new TronException('explorer is not activated.');
        }

        return $this->providers['explorer'];
    }

    /**
     * Event server
     *
     * @throws TronException
     */
    public function eventServer(): HttpProviderInterface
    {
        if (!array_key_exists('eventServer', $this->providers)) {
            throw new TronException('Event server is not activated.');
        }

        return $this->providers['eventServer'];
    }

    /**
     * Basic query to nodes
     *
     * @param $url
     * @throws TronException
     */
    public function request(string $url, array $params = [], string $method = 'post'): array
    {
        $split = explode('/', $url);
        if(in_array($split[0], ['walletsolidity', 'walletextension'])) {
            $response = $this->solidityNode()->request($url, $params, $method);
        } elseif($split[0] == 'event') {
            $response = $this->eventServer()->request($url, $params, 'get');
        } elseif ($split[0] == 'trx-sign') {
            $response = $this->signServer()->request($url, $params, 'post');
        } elseif($split[0] == 'api') {
            $response = $this->explorer()->request($url, $params, 'get');
        }else {
            $response = $this->fullNode()->request($url, $params, $method);
        }

        return $response;
    }

    /**
     * Check connections
     */
    public function isConnected(): array
    {
        $array = [];
        foreach ($this->providers as $key => $value) {
            $array[] = [
                $key => boolval($value->isConnected())
            ];
        }

        return $array;
    }
}