<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Exception;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * If the request cannot be sent due to a network failure of any kind, including a timeout
 */
class NetworkException extends ClientException implements NetworkExceptionInterface
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
