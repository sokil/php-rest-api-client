<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Request;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractApiRequest
{
    /**
     * @param array<string, string|array> $queryParams
     * @param null|array $postBody MUST be set for POST and PUT methods, may be of any structure
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $queryParams = [],
        public readonly ?array $postBody = null,
        public readonly array $headers = [],
    ) {
        if (!in_array($this->method, ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'])) {
            throw new \OutOfRangeException('Invalid HTTP method passed');
        }

        if (($this->method === 'POST' || $this->method === 'PUT') && $this->postBody === null) {
            throw new \InvalidArgumentException('Body can not be null for POST and PUT requests');
        }
    }

    abstract public function buildResponse(
        RequestInterface $httpRequest,
        ResponseInterface $httpResponse,
    ): ?object;

    protected function unserialize(ResponseInterface $response): array
    {
        $contentType = $response->getHeaderLine('Content-Type');
        $responseContent = (string) $response->getBody();

        switch (true) {
            default:
            case str_contains($contentType, 'application/json'):
                if (empty($responseContent)) {
                    return [];
                }

                $responseBody = (array) json_decode(
                    json: $responseContent,
                    associative: true,
                    flags: JSON_THROW_ON_ERROR
                );
                break;
        }

        return $responseBody;
    }
}
