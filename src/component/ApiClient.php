<?php

namespace ApiClient\component;

use ApiClient\exception\TransportException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use ApiClient\exception\ApiException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ApiClient
{
    private const API_URL = 'https://zmzqn4e0ml.api.quickmocker.com/';

    private const CONNECT_TIMEOUT = 4.0;
    private const REQUEST_TIMEOUT = 16.0;

    private const MAX_RETRIES = 8;
    private const RETRY_INTERVAL = 1;

    /** @var ClientInterface */
    private $httpClient;

    /** @var string|null */
    private $token;

    public function __construct()
    {
        $this->httpClient = new Client([
            'allow_redirects' => false,
            'base_uri' => self::API_URL,
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'timeout' => self::REQUEST_TIMEOUT,
            'decode_content' => true,
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip,deflate',
                'Connection' => 'keep-alive',
                'User-Agent' => 'API PHP Client',
            ],
        ]);
    }

    private function call(string $method, string $endpoint, array $params)
    {
        $request = $this->buildRequest($method, $endpoint, $params);
        try {
            $response = $this->httpClient->send($request);
        } catch (\Throwable $e) {
            throw new ApiException($e->getMessage(), $e->getCode(), $e);
        }

        return $this->processResponse($response);
    }

    private function callWithRetries(string $method, string $endpoint, array $params = [])
    {
        $retries = 0;
        $lastException = null;
        do {
            try {
                return $this->call($method, $endpoint, $params);
            } catch (ApiException $e) {
                $lastException = $e;
            } catch (\Throwable $e) {
                $lastException = $e;
            }
            sleep(self::RETRY_INTERVAL);
        } while (++$retries < self::MAX_RETRIES);

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new \LogicException('Something went wrong with retries.');
    }

    private function buildRequest(string $method, string $endpoint, array $params): RequestInterface
    {
        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                $uri = $endpoint . (!empty($params) ? ('?' . http_build_query($params)) : '');
                $body = null;
                $headers = [];
                break;
            case 'POST':
                $uri = $endpoint;
                $body = json_encode($params);
                $headers = [
                    'Content-Type' => 'application/json',
                ];
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown method "%s".', $method));
        }
        return new Request($method, $uri, $headers, $body);
    }

    private function processResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $arrayResponse = json_decode($body, false);
        $errorCode = json_last_error();
        if ($errorCode !== JSON_ERROR_NONE) {
            throw new TransportException(sprintf('Invalid JSON: %d. Response: {%s}', $errorCode, $body));
        }

        $statusCode = $response->getStatusCode();
        switch ($statusCode) {
            case 200:
            case 201:
                return $arrayResponse;
            default:
                throw new ApiException('Error: ' . $response->getBody(), $statusCode);
        }
    }

    public function auth(string $login, string $password)
    {
        $response = $this->callWithRetries('GET', 'auth', [
            'login' => $login,
            'pass' => $password,
        ]);
        return $response;

    }
}