<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Auth\OAuth2;

/**
 * If {@see \Sokil\RestApiClient\Request\AbstractApiRequest} implements this interface, required
 * injection of header with Bearer Authorization header with token obtained from authorization server
 */
interface OAuth2AuthorizationAwareRequestInterface
{
}
