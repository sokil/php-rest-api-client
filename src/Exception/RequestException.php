<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Exception;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * If a request cannot be sent because the request message is not a well-formed HTTP request or is missing
 * some critical piece of information (such as a Host or Method)
 */
class RequestException extends ClientException implements RequestExceptionInterface
{
    public function __construct(
        public readonly RequestInterface $request,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
