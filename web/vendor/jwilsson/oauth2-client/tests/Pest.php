<?php

declare(strict_types=1);

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2\Grant;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

function setup_client(ResponseInterface $response = null): Client
{
    $response ??= create_response();
    $client = new Client();

    $client->addResponse($response);

    return $client;
}

function setup_grant(string $grant, array $options = [], ?Client $client = null): Grant
{
    $options = array_replace_recursive([
        'client_id' => '2bfe9d72a4aae8f06a31025b7536be80',
        'client_secret' => '9d667c2b7fae7a329f32b6df17926154',
        'redirect_uri' => 'https://example.com/callback',
        'endpoints' => [
            'auth_url' => 'https://provider.com/oauth2/auth',
            'token_url' => 'https://provider.com/oauth2/token',
        ],
    ], $options);

    return new $grant(
        $options,
        $client ?? setup_client(),
        Psr17FactoryDiscovery::findRequestFactory(),
        Psr17FactoryDiscovery::findStreamFactory()
    );
}

function create_request(string $method = 'GET', string $url = 'https://example.com'): RequestInterface
{
    $psr17Factory = new Psr17Factory();

    return $psr17Factory->createRequest($method, $url);
}

function create_response(int $status = 200, array $parameters = []): ResponseInterface
{
    $psr17Factory = new Psr17Factory();
    $response = $psr17Factory->createResponse($status);

    $parameters = array_replace([
        'access_token' => '2YotnFZFEjr1zCsicMWpAA',
        'custom_param' => 'custom_value',
        'expires_in' => 3600,
        'refresh_token' => 'tGzv3JOkF0XG5Qx2TlKWIA',
        'token_type' => 'example',
    ], $parameters);

    $body = json_encode(
        array_filter($parameters)
    );

    return $response->withBody(
        $psr17Factory->createStream($body)
    );
}
