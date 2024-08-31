<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Auth\OAuth2;

/**
 * Used to authorize on authorization server, store and refresh stored access token
 *
 * Request that require oauth2 authorization MUST implement {@see OAuth2AuthorizationAwareRequestInterface}
 */
interface OAuth2ClientInterface
{
    /**
     * Get access token from internal storage.
     * If token is not in storage, renew token and place it to storage.
     */
    public function getAccessToken(): string;

    /**
     * Authorize on auth server, get new token and update it in storage
     */
    public function refreshAccessToken(): string;
}
