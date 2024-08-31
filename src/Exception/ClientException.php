<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Exception;

use Psr\Http\Client\ClientExceptionInterface;

/**
 * A Client MUST throw an instance of client exception if and only if it is unable
 * to send the HTTP request at all or if the HTTP response could not be parsed into a
 * PSR-7 response object.
 */
class ClientException extends \RuntimeException implements ClientExceptionInterface
{
}
