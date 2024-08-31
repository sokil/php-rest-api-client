<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Auth\OAuth2\AuthTokenStorage;

use Sokil\RestApiClient\Auth\OAuth2\AuthTokenStorage\Exception\AuthTokenStorageException;
use Psr\SimpleCache\CacheInterface;

/**
 * Supports any PSR-16 compatible cache
 */
class SimpleCacheAuthTokenStorage implements AuthTokenStorageInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @param non-empty-string $key
     * @return non-empty-string
     */
    public function get(string $key): string
    {
        try {
            /** @var non-empty-string $value */
            $value = (string) $this->cache->get($key);
        } catch (\Throwable $e) {
            throw new AuthTokenStorageException('Get auth token failed', 0, $e);
        }

        return $value;
    }

    public function set(string $key, string $value, int $expireTtl): bool
    {
        if ($expireTtl < 0) {
            throw new \InvalidArgumentException('invalid expireTtl value');
        }

        try {
            return $this->cache->set($key, $value, $expireTtl);
        } catch (\Throwable $e) {
            throw new AuthTokenStorageException('Set auth token failed', 0, $e);
        }
    }
}
