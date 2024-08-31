<?php

declare(strict_types=1);

namespace Sokil\RestApiClient\Test\Unit;

use Sokil\RestApiClient\Auth\OAuth2\OAuth2AuthorizationAwareRequestInterface;
use Sokil\RestApiClient\Auth\OAuth2\OAuth2ClientInterface;
use Sokil\RestApiClient\Request\AbstractApiRequest;
use Sokil\RestApiClient\RestApiClient;
use Sokil\RestApiClient\Test\Stub\TestResponseDto;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface as ResponseFactory;

class RestApiWithOauthClientTest extends TestCase
{
    public function testCall(): void
    {
        $expected = new TestResponseDto('value');
        $client = $this->getMockClientWithCallResultConfigured(
            [
                new MockResponse(
                    json_encode([
                        'id' => $expected->value,
                    ]),
                    [
                        'response_headers' => [
                            'Content-Type' => 'application/json',
                        ],
                    ]
                )
            ]
        );

        $request = new class ('GET', '/v1/id') extends AbstractApiRequest implements
            OAuth2AuthorizationAwareRequestInterface
        {
            public function buildResponse(RequestInterface $httpRequest, ResponseInterface $httpResponse): ?object
            {
                $body = $this->unserialize($httpResponse);

                if (empty($body) || empty($body['id'])) {
                    throw new \Exception('Response body must contains id key');
                }

                return new TestResponseDto((string) $body['id']);
            }
        };

        /** @var TestResponseDto $actual */
        $actual = $client->call($request);

        $this->assertEquals($expected->value, $actual->value);
    }

    public function testCallWithRetry(): void
    {
        $client = $this->getMockClientWithCallResultConfigured(
            [
                new MockResponse(
                    '',
                    [
                        'http_code' => 403,
                        'response_headers' => [
                            'Content-Type' => 'application/json',
                        ],
                    ]
                ),
                new MockResponse(
                    '',
                )
            ],
        );

        $request = new class ('GET', '/v1/id') extends AbstractApiRequest implements
            OAuth2AuthorizationAwareRequestInterface
        {
            public function buildResponse(RequestInterface $httpRequest, ResponseInterface $httpResponse): ?object
            {
                return null;
            }
        };

        $actual = $client->call($request);

        $this->assertNull($actual);
    }

    private function getMockClientWithCallResultConfigured(
        callable|iterable|ResponseFactory $responseFactory
    ): RestApiClient {
        $httpClient = new MockHttpClient($responseFactory);
        $httpClientPsrAdapter = new Psr18Client($httpClient);

        return new RestApiClient(
            $httpClientPsrAdapter,
            $httpClientPsrAdapter,
            $httpClientPsrAdapter,
            new class () implements OAuth2ClientInterface {
                public function refreshAccessToken(): string
                {
                    return 'my-new-access-token';
                }

                public function getAccessToken(): string
                {
                    return $this->refreshAccessToken();
                }
            },
            null
        );
    }
}
