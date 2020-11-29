<?php

namespace ApiClient\model;

use ApiClient\component\ApiClient;

class User
{
    /** @var ApiClient|null */
    private $apiClient;

    /** @var string|null */
    private $username;

    /**
     * User constructor.
     * @throws \Throwable
     */
    public function __construct(string $login, string $password)
    {
        $this->getApiClient()->auth($login, $password);
    }

    public function getApiClient(): ApiClient
    {
        if ($this->apiClient === null) {
            $this->apiClient = new ApiClient();
        }

        return $this->apiClient;
    }

    /**
     * @throws \Throwable
     */
    public function getData(): array
    {
        if ($this->username === null) {
            throw new \InvalidArgumentException('Params username cannot be empty.');
        }
        return $this->getApiClient()->getData($this->username);
    }

    /**
     * @throws \Throwable
     */
    public function updateData(int $uid, array $params): void
    {
        $this->getApiClient()->updateData($uid, $params);
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $usename): void
    {
        $this->username = $usename;
    }
}