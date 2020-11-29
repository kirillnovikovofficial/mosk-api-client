<?php

namespace ApiClient\component;

use ApiClient\exception\NotAuthException;
use ApiClient\exception\NotFoundException;
use ApiClient\exception\TransportException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use ApiClient\exception\ApiException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ApiClient
{
    private const API_URL = 'http://localhost';

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

    private function call(string $method, string $endpoint, array $params): array
    {
        $request = $this->buildRequest($method, $endpoint, $params);
        try {
            $response = $this->httpClient->send($request);
        } catch (\Throwable $e) {
            throw new ApiException($e->getMessage(), $e->getCode(), $e);
        }

        return $this->processResponse($response);
    }

    /**
     * @throws \Throwable
     */
    private function callWithRetries(string $method, string $endpoint, array $params = []): array
    {
        $retries = 0;
        $lastException = null;
        $notRetry = [404];
        do {
            try {
                return $this->call($method, $endpoint, $params);
            } catch (ApiException $e) {
                $lastException = $e;
            } catch (\Throwable $e) {
                $lastException = $e;
                if (in_array($e->getCode(), $notRetry)) {
                    throw $lastException;
                }
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
        $arrayResponse = json_decode($body, true);
        $errorCode = json_last_error();
        if ($errorCode !== JSON_ERROR_NONE) {
            throw new TransportException(sprintf('Error: invalid JSON (%d). Response: {%s}', $errorCode, $body));
        }

        $statusCode = $response->getStatusCode();
        switch ($statusCode) {
            case 200:
            case 201:
                return $arrayResponse;
            case 404:
                throw new NotFoundException(sprintf('Error: %s', $response->getBody()), $statusCode);
            default:
                throw new ApiException(sprintf('Error: %s', $response->getBody()), $statusCode);
        }
    }

    /**
     * @throws \Throwable
     */
    public function auth(string $login, string $password): void
    {
        $response = $this->callWithRetries('GET', 'auth', [
            'login' => $login,
            'pass' => $password,
        ]);
        $this->token = $response['token'] ?? null;
        if ($this->token === null) {
            throw new ApiException(sprintf('Error: invalid response. Response: {%s}', json_encode($response)));
        }
    }

    /**
     * @throws \Throwable
     */
    public function getData(string $username): array
    {
        if ($this->token === null) {
            throw new NotAuthException();
        }
        return $this->callWithRetries('GET', "get-user/$username", [
            'token' => $this->token,
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function updateData(int $uid, array $params): void
    {
        if ($this->token === null) {
            throw new NotAuthException();
        }
        $this->callWithRetries('POST', "user/$uid/update?token={$this->token}", [
            $params
        ]);
    }
}