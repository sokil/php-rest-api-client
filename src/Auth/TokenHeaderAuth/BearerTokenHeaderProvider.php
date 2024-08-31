<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Auth\TokenHeaderAuth;

final readonly class BearerTokenHeaderProvider implements TokenHeaderProviderInterface
{
    public function __construct(
        private readonly string $secret,
    ) {
    }

    public function getHeaderName(): string
    {
        return 'Authorization';
    }

    public function getSecret(): string
    {
        return 'Bearer ' . $this->secret;
    }
}
