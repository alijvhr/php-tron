<?php declare(strict_types=1);

namespace Tron\Provider;

use GuzzleHttp\{Psr7\Request, Client, ClientInterface};
use Psr\Http\Message\StreamInterface;
use Tron\Exception\{NotFoundException, TronException};
use Tron\Support\Utils;

class HttpProvider implements HttpProviderInterface
{
    /**
     * HTTP Client Handler
     *
     * @var ClientInterface.
     */
    protected \GuzzleHttp\Client $httpClient;

    /**
     * Server or RPC URL
     */
    protected string $host;

    /**
     * Waiting time
     */
    protected int $timeout;

    /**
     * Get custom headers
     */
    protected array $headers;

    /**
     * Create an HttpProvider object
     *
     * @throws TronException
     */
    public function __construct(string           $host, int $timeout = 30000,
                                bool             $user = false, bool $password = false,
                                array            $headers = [], /**
                                 * Get the pages
                                 */
                                protected string $statusPage = '/')
    {
        if(!Utils::isValidUrl($host)) {
            throw new TronException('Invalid URL provided to HttpProvider');
        }

        if(is_nan($timeout) || $timeout < 0) {
            throw new TronException('Invalid timeout duration provided');
        }

        if(!Utils::isArray($headers)) {
            throw new TronException('Invalid headers array provided');
        }

        $this->host = $host;
        $this->timeout = $timeout;
        $this->headers = $headers;

        $this->httpClient = new Client([
            'base_uri'  =>  $host,
            'timeout'   =>  $timeout,
            'auth'      =>  $user && [$user, $password]
        ]);
    }

    /**
     * Enter a new page
     */
    public function setStatusPage(string $page = '/'): void
    {
        $this->statusPage = $page;
    }

    /**
     * Check connection
     *
     * @throws TronException
     */
    public function isConnected() : bool
    {
        $response = $this->request($this->statusPage);

        if(array_key_exists('blockID', $response)) {
            return true;
        } elseif(array_key_exists('status', $response)) {
            return true;
        }
        return false;
    }

    /**
     * Getting a host
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Getting timeout
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * We send requests to the server
     *
     * @return array|mixed
     * @throws TronException
     */
    public function request(string $url, array $payload = [], string $method = 'get'): array
    {
        $method = strtoupper($method);

        if(!in_array($method, ['GET', 'POST'])) {
            throw new TronException('The method is not defined');
        }

        $options = [
            'headers'   => $this->headers,
            'body'      => json_encode($payload)
        ];

        $request = new Request($method, $url, $options['headers'], $options['body']);
        $rawResponse = $this->httpClient->send($request, $options);

        return $this->decodeBody(
            $rawResponse->getBody(),
            $rawResponse->getStatusCode()
        );
    }

    /**
     * Convert the original answer to an array
     *
     * @return array|mixed
     */
    protected function decodeBody(StreamInterface $stream, int $status): array
    {
        $decodedBody = json_decode($stream->getContents(),true);

        if((string)$stream === 'OK') {
            $decodedBody = [
                'status'    =>  1
            ];
        }elseif ($decodedBody == null || !is_array($decodedBody)) {
            $decodedBody = [];
        }

        if($status == 404) {
            throw new NotFoundException('Page not found');
        }

        return $decodedBody;
    }
}
