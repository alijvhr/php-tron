<?php

namespace Tron;

use GuzzleHttp\Client;
use Tron\Exceptions\TronErrorException;

class Api
{
    public function __construct(private Client $_client)
    {
    }

    public function getClient(): Client
    {
        return $this->_client;
    }

    /**
     * Abstracts some common functionality like formatting the post data
     * along with error handling.
     *
     * @throws TronErrorException
     */
    public function post(string $endpoint, array $data = [], bool $returnAssoc = false)
    {
        if (count($data) !== 0) {
            $data = ['json' => $data];
        }

        $stream = (string)$this->getClient()->post($endpoint, $data)->getBody();
        $body = json_decode($stream, $returnAssoc);

        $this->checkForErrorResponse($returnAssoc, $body);

        return $body;
    }

    /**
     * Abstracts some common functionality just to preserve usage like post
     *
     * @throws TronErrorException
     */
    public function get(string $endpoint, array $data = [], bool $returnAssoc = false)
    {
        if (count($data) !== 0) {
            $data = ['query' => $data];
        }

        $stream = (string)$this->getClient()->get($endpoint, $data)->getBody();
        $body = json_decode($stream, $returnAssoc);

        $this->checkForErrorResponse($returnAssoc, $body);

        return $body;
    }

    /**
     * Check if the response has an error and throw it.
     *
     * @param $body
     * @throws TronErrorException
     */
    private function checkForErrorResponse(bool $returnAssoc, $body): void
    {
        if ($returnAssoc) {
            if (isset($body['Error'])) {
                throw new TronErrorException($body['Error']);
            } elseif (isset($body['code']) && isset($body['message'])) {
                throw new TronErrorException($body['code'] . ': ' . hex2bin($body['message']));
            }
        }

        if (isset($body->Error)) {
            throw new TronErrorException($body->Error);
        } elseif (isset($body->code) && isset($body->message)) {
            throw new TronErrorException($body->code . ': ' . hex2bin($body->message));
        }
    }
}
