<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Auth\OAuth2;

use Sokil\RestApiClient\Auth\OAuth2\AuthTokenStorage\AuthTokenStorageInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Used to authorize on authorization server, store and refresh stored access token
 */
class OAuth2Client implements OAuth2ClientInterface
{
    private const ACCESS_TOKEN_CACHE_KEY = 'accessToken';

    public function __construct(
        private readonly string $authUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly AuthTokenStorageInterface $authTokenStorage,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Get access token from internal storage.
     * If token is not in storage, renew token and place it to storage.
     */
    public function getAccessToken(): string
    {
        try {
            $accessToken = $this->authTokenStorage->get(self::ACCESS_TOKEN_CACHE_KEY);
        } catch (\Throwable $e) {
            $this->logger->critical(
                '[RestApiClient][OAuth2] Access token storage is not available',
                ['exception' => $e],
            );

            $accessToken = null;
        }

        if (null === $accessToken) {
            $accessToken = $this->refreshAccessToken();
        }

        return $accessToken;
    }

    /**
     * Authorize on auth server, get new token and update it in storage
     */
    public function refreshAccessToken(): string
    {
        // set authorize request
        try {
            $request = $this->requestFactory->createRequest(
                'POST',
                $this->authUrl
            );

            $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

            $request->getBody()->write(
                http_build_query([
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ])
            );

            $response = $this->httpClient->sendRequest($request);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException(sprintf(
                    'Authorization server response code %d',
                    $response->getStatusCode()
                ));
            }
        } catch (\Throwable $e) {
            $this->logger->critical(
                '[RestApiClient][OAuth2] Authorization server error',
                [
                    'exception' => $e,
                    'response' => $response ?? null,
                ],
            );

            throw $e;
        }

        // parse response
        $responseData = json_decode((string) $response->getBody(), true);

        if (
            empty($responseData['access_token']) ||
            !is_string($responseData['access_token']) ||
            empty($responseData['expires_in']) ||
            !is_int($responseData['expires_in'])
        ) {
            throw new \RuntimeException('Invalid authorization server response');
        }

        // store to cache
        try {
            $isStored = $this->authTokenStorage->set(
                self::ACCESS_TOKEN_CACHE_KEY,
                $responseData['access_token'],
                $responseData['expires_in'],
            );

            if (!$isStored) {
                throw new \RuntimeException('Error saving access token to storage');
            }
        } catch (\Throwable $e) {
            $this->logger->critical(
                '[RestApiClient][OAuth2] Error saving access token to storage',
                ['exception' => $e],
            );
        }

        return $responseData['access_token'];
    }
}
