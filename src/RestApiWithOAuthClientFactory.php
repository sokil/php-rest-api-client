<?php

declare(strict_types=1);

namespace Sokil\RestApiClient;

use Sokil\RestApiClient\Auth\OAuth2\AuthTokenStorage\AuthTokenStorageInterface;
use Sokil\RestApiClient\Auth\OAuth2\OAuth2Client;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;

class RestApiWithOAuthClientFactory
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly AuthTokenStorageInterface $authTokenStorage,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function build(
        string $apiBaseUrl,
        string $apiAuthUrl,
        string $apiAuthClientId,
        string $apiAuthClientSecret,
    ): RestApiClient {
        return new RestApiClient(
            $this->httpClient,
            $this->requestFactory,
            $this->uriFactory,
            new OAuth2Client(
                $apiAuthUrl,
                $apiAuthClientId,
                $apiAuthClientSecret,
                $this->httpClient,
                $this->requestFactory,
                $this->authTokenStorage,
                $this->logger
            ),
            null,
            $apiBaseUrl
        );
    }
}
