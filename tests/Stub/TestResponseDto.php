<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Test\Stub;

class TestResponseDto
{
    public function __construct(
        public readonly string $value,
    ) {
    }
}
