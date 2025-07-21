<?php declare(strict_types=1);

namespace Tron\Provider;


interface HttpProviderInterface
{
    /**
     * Enter a new page
     */
    public function setStatusPage(string $page = '/'): void;

    /**
     * Check connection
     */
    public function isConnected(): bool;

    /**
     * We send requests to the server
     */
    public function request(string $url, array $payload = [], string $method = 'get'): array;
}
