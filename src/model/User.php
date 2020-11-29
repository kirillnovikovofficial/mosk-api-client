<?php

namespace ApiClient\model;

use ApiClient\component\ApiClient;

class User
{
    /** @var ApiClient|null */
    private $apiClient;

    public function __construct(string $login, string $password)
    {
        print_r($this->getApiClient()->auth($login, $password));
    }

    public function getApiClient(): ApiClient
    {
        if ($this->apiClient === null) {
            $this->apiClient = new ApiClient();
        }

        return $this->apiClient;
    }
}