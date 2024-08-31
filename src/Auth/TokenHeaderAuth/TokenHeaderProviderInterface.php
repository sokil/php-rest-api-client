<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Auth\TokenHeaderAuth;

interface TokenHeaderProviderInterface
{
    public function getHeaderName(): string;

    public function getSecret(): string;
}
