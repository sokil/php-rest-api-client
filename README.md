# REST API Client

## Installation

```shell
$ composer require sokil/php-rest-api-client
```

## Configuration

### Base API URI

Any request may contain absolute path to REST API. If all requests performed to same api, you may define base path:

```
$restApiClient = new RestApiClient(
    $psrHttpClient,
    $psrRequestFactory,
    $psrUriFactory,
    null,
    null,
    "https://api.example.com/some-prefix"
);
```

Base API URI must be without training slash.

Merging rules:

| Base API URI              | Request path              | Actual Requested URI               |
|---------------------------|---------------------------|------------------------------------|
| Not defined               | Absolute URI              | Absolute URI                       |
| Not defined               | Relative URI              | Error                              |
| http://example.com        | /resource                 | http://example.com/resource        |
| http://example.com/prefix | /resource                 | http://example.com/prefix/resource |
| http://example.com        | http://other.com/resource | http://other.com/resource          |
| http://example.com/prefix | http://other.com/resource | http://other.com/resource          |

## Usage

Creation of http client. Client is based on PSR-18 Http Client and PSR-17 Http Factories

```php
<?php

use Sokil\RestApiClient\RestApiClient;

$restApiClient = new RestApiClient(
    $psrHttpClient,
    $psrRequestFactory,
    $psrUriFactory,
    null,
    null,
);
```

Create request class

```php
<?php

use Psr\Http\Message\ResponseInterface;
use Sokil\RestApiClient\Request\AbstractApiRequest

class GetResourceByIdRequest extends AbstractApiRequest
{
    public function __construct(
        private readonly string $id
    ) {
        parent::__construct(
            'GET',
            sprintf('/v1/resources/%s', $this->id),
        );
    }

    public function parseResponse(ResponseInterface $response): ?object
    {
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Response code not ok');
        }

        return new SomeResourceDTO();
    }
}
```

Calling of API endpoint:

```php

class MyService
{
    public function getResource(): object
    {
        $id = 'id_string';
        $result = $this->restApiClient->call(new GetResourceByIdRequest($id))

        return $result;
    }
}
```

### Usage with OAuth authorization

REST client must be build with additional OAuth2 client argument

```php
<?php

use Sokil\RestApiClient\RestApiClient;
use Sokil\RestApiClient\Auth\OAuth2\OAuth2Client;
use Sokil\RestApiClient\Auth\OAuth2\AuthTokenStorage\SimpleCacheAuthTokenStorage;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Psr\Log\NullLogger;

$symfonyClient = HttpClient::createForBaseUri(getenv('API_HOST'));
$psrHttpClient = new Psr18Client($symfonyClient);

// used to authorize on authorization server, store and refresh stored access token
$oAuth2Client = new OAuth2Client(
    getenv('AUTH_API_HOST'),
    getenv('AUTH_CLIENT_ID'),
    getenv('AUTH_CLIENT_SECRET'),
    $psrHttpClient,
    $psrHttpClient,
    new SimpleCacheAuthTokenStorage(new Psr16Cache(new ArrayAdapter())),
    new NullLogger(),
);

$restApiClient = new RestApiClient(
    $psrHttpClient,
    $psrHttpClient,
    $psrHttpClient,
    $psrHttpClient,
    $oAuth2Client,
);
```

Your request class must implement `OAuth2AuthorizationAwareInterface`

```php
<?php

use Psr\Http\Message\ResponseInterface;
use Sokil\RestApiClient\Request\AbstractApiRequest
use Sokil\RestApiClient\Auth\OAuth2\OAuth2AuthorizationAwareInterface

class GetResourceByIdRequest extends AbstractApiRequest implements OAuth2AuthorizationAwareInterface
{
    public function __construct(
        private readonly string $id
    ) {
        parent::__construct(
            'GET',
            sprintf('/v1/resource/%s', $this->id),
        );
    }

    public function parseResponse(ResponseInterface $response): ?object
    {
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Response code not ok');
        }

        $body = $this->jsonResponseToArray($response);

        if (empty($body) || !isset($body['resource'])) {
            throw new \Exception('Response body invalid');
        }

        return new YourResponseDto(..$values);
    }
}
```

### Usage with token based authorization

REST client must be build with additional token secret provider client argument

```php
<?php

use Sokil\RestApiClient\RestApiClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Sokil\RestApiClient\HeaderSecretAuth\SimpleSecretProvider;
use Psr\Log\NullLogger;

$symfonyClient = HttpClient::createForBaseUri(getenv('API_HOST'));
$psrHttpClient = new Psr18Client($symfonyClient);

// used to authorize on authorization server, store and refresh stored access token
$oAuth2Client = ;

$restApiClient = new RestApiClient(
    $psrHttpClient,
    $psrHttpClient,
    $psrHttpClient,
    null,
    new SimpleHeaderSecretProvider(
        getenv('TOKAN_AUTH_HEADER_NAME'),
        getenv('TOKAN_AUTH_SECRET'),
    )
);
```

Your request class must implement `HeaderSecretAuthorizationAwareInterface`

```php
<?php

use Psr\Http\Message\ResponseInterface;
use Sokil\RestApiClient\Request\AbstractApiRequest
use Sokil\RestApiClient\Auth\OAuth2\OAuth2AuthorizationAwareInterface

class GetResourceByIdRequest extends AbstractApiRequest implements HeaderSecretAuthorizationAwareInterface
{
    public function __construct(
        private readonly string $id
    ) {
        parent::__construct(
            'GET',
            sprintf('/v1/resource/%s', $this->id),
        );
    }

    public function parseResponse(ResponseInterface $response): ?object
    {
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Response code not ok');
        }

        $body = $this->jsonResponseToArray($response);

        if (empty($body) || !isset($body['resource'])) {
            throw new \Exception('Response body invalid');
        }

        return new YourResponseDto(..$values);
    }
}
```
