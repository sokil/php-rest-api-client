<?php

declare(strict_types=1);

namespace Sokil\RestApiClient;

use Sokil\RestApiClient\Exception\ClientException;
use Sokil\RestApiClient\Exception\NetworkException;
use Sokil\RestApiClient\Exception\ParseResponseException;
use Sokil\RestApiClient\Exception\RequestException;
use Sokil\RestApiClient\Auth\TokenHeaderAuth\TokenHeaderProviderInterface;
use Sokil\RestApiClient\Auth\TokenHeaderAuth\TokenHeaderAuthorizationAwareRequestInterface;
use Sokil\RestApiClient\Auth\OAuth2\OAuth2AuthorizationAwareRequestInterface;
use Sokil\RestApiClient\Auth\OAuth2\OAuth2ClientInterface;
use Sokil\RestApiClient\Request\AbstractApiRequest;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;

class RestApiClient
{
    /**
     * @param ?OAuth2ClientInterface $oAuth2Client If not defined, requests does not require oauth2 authorization
     */
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly ?OAuth2ClientInterface $oAuth2Client,
        private readonly ?TokenHeaderProviderInterface $tokenHeaderProvider,
        private readonly string $baseUri = '',
    ) {
        if ($this->baseUri && str_ends_with($this->baseUri, '/')) {
            throw new \InvalidArgumentException('Base path must be defined without trailing slash');
        }
    }

    /**
     * @throws ParseResponseException|ClientExceptionInterface
     */
    public function call(AbstractApiRequest $apiRequest): ?object
    {
        try {
            $httpRequest = $this->buildHttpRequest($apiRequest);

            if ($this->oAuth2Client && ($apiRequest instanceof OAuth2AuthorizationAwareRequestInterface)) {
                $httpResponse = $this->sendRequestWithOauth2ForbiddenRetry($httpRequest, true);
            } else {
                $httpResponse = $this->sendRequest($httpRequest);
            }
        } catch (RequestExceptionInterface $e) {
            throw new RequestException(
                $e->getRequest(),
                $e->getMessage(),
                0,
                $e
            );
        } catch (NetworkExceptionInterface $e) {
            throw new NetworkException(
                $e->getRequest(),
                $e->getMessage(),
                0,
                $e
            );
        } catch (\Throwable $e) {
            throw new ClientException(
                $e->getMessage(),
                0,
                $e
            );
        }

        try {
            $parsedResponse = $apiRequest->buildResponse($httpRequest, $httpResponse);
        } catch (\Throwable $e) {
            throw new ParseResponseException($e->getMessage(), (int) $e->getCode(), $e);
        }

        return $parsedResponse;
    }

    /**
     * @throws ClientExceptionInterface
     */
    private function sendRequestWithOauth2ForbiddenRetry(
        RequestInterface $httpRequest,
        bool $retryDueTo403,
    ): ResponseInterface {
        $response = $this->sendRequest($httpRequest);

        if ($retryDueTo403 && $response->getStatusCode() === 403) {
            /** @psalm-suppress PossiblyNullReference Already checked when call method */
            $this->oAuth2Client->refreshAccessToken();

            return $this->sendRequestWithOauth2ForbiddenRetry($httpRequest, false);
        } else {
            return $response;
        }
    }

    /**
     * @throws ClientExceptionInterface
     */
    private function sendRequest(RequestInterface $httpRequest): ResponseInterface
    {
        $response = $this->httpClient->sendRequest($httpRequest);

        return $response;
    }

    /**
     * @throws \JsonException
     */
    private function buildHttpRequest(AbstractApiRequest $request): RequestInterface
    {
        if ($this->baseUri && str_starts_with($request->path, '/')) {
            $uri = $this->baseUri . $request->path;
        } else {
            // base uri also may be configured in http client, so this pay may also be relative
            $uri = $request->path;
        }

        $uri = $this->uriFactory->createUri($uri);

        if (!empty($request->queryParams)) {
            $uri = $uri->withQuery(http_build_query($request->queryParams));
        }

        $httpRequest = $this->requestFactory->createRequest(
            $request->method,
            $uri,
        );

        foreach ($request->headers as $headerName => $headerValue) {
            $httpRequest = $httpRequest->withHeader($headerName, $headerValue);
        }

        if ($request instanceof OAuth2AuthorizationAwareRequestInterface && $this->oAuth2Client) {
            $bearerToken = $this->oAuth2Client->getAccessToken();

            $httpRequest = $httpRequest->withHeader(
                'Authorization',
                sprintf('Bearer %s', $bearerToken),
            );
        } elseif (
            $request instanceof TokenHeaderAuthorizationAwareRequestInterface &&
            $this->tokenHeaderProvider
        ) {
            $httpRequest = $httpRequest->withHeader(
                $this->tokenHeaderProvider->getHeaderName(),
                $this->tokenHeaderProvider->getSecret(),
            );
        }

        if (in_array($request->method, ['POST', 'PUT'])) {
            $requestContentType = $httpRequest->getHeaderLine('Content-type');

            if (empty($requestContentType)) {
                $requestContentType = 'application/json';
                $httpRequest = $httpRequest->withHeader('Content-type', $requestContentType);
            }

            switch (true) {
                case str_contains($requestContentType, 'application/json'):
                    $serialisedBody = \json_encode($request->postBody, JSON_THROW_ON_ERROR);
                    break;
                default:
                    throw new \LogicException('Unknown content type of request');
            }

            $httpRequest->getBody()->write($serialisedBody);
        }

        return $httpRequest;
    }
}
