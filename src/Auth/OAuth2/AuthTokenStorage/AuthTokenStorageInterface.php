<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Auth\OAuth2\AuthTokenStorage;

interface AuthTokenStorageInterface
{
    /**
     * @param non-empty-string $key
     * @return non-empty-string
     */
    public function get(string $key): string;

    public function set(string $key, string $value, int $expireTtl): bool;
}
