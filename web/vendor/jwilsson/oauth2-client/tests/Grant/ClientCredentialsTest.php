<?php

declare(strict_types=1);

use OAuth2\Grant\ClientCredentials;
use OAuth2\Grant\Exception\GrantException;

it('should request an access token', function () {
    $client = setup_client();
    $grant = setup_grant(ClientCredentials::class, [], $client);

    $token = $grant->requestAccessToken([
        'custom_param' => 'custom_value',
    ]);

    $request = $client->getLastRequest();
    $body = $request->getBody()->__toString();

    expect($request->getMethod())->toBe('POST');
    expect($request->getUri()->__toString())->toBe('https://provider.com/oauth2/token');
    expect($request->getHeaderLine('Authorization'))->toBe(
        'Basic MmJmZTlkNzJhNGFhZThmMDZhMzEwMjViNzUzNmJlODA6OWQ2NjdjMmI3ZmFlN2EzMjlmMzJiNmRmMTc5MjYxNTQ='
    );

    expect($body)->toContain('grant_type=client_credentials');
    expect($body)->toContain('custom_param=custom_value');
});

it('should throw an exception when an access token request fails', function () {
    $response = create_response(400, [
        'error' => 'Invalid request',
    ]);

    $client = setup_client($response);
    $grant = setup_grant(ClientCredentials::class, [], $client);

    expect(fn () => $grant->requestAccessToken())->toThrow(GrantException::class);
});

it('should throw an exception when no access token is present in response', function () {
    $response = create_response(200, [
        'access_token' => null,
    ]);

    $client = setup_client($response);
    $grant = setup_grant(ClientCredentials::class, [], $client);

    expect(fn () => $grant->requestAccessToken())->toThrow(GrantException::class);
});
