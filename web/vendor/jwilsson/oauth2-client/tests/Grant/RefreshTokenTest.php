<?php

declare(strict_types=1);

use OAuth2\Grant\Exception\GrantException;
use OAuth2\Grant\RefreshToken;

it('should request an access token', function () {
    $client = setup_client();
    $grant = setup_grant(RefreshToken::class, [], $client);
    $token = $grant->requestAccessToken('6c8a7d4aa21708a432174e4cb5c6cfaf0218f5f3e52f9a76a7d95d2aaade2c83', [
        'custom_param' => 'custom_value',
    ]);

    $request = $client->getLastRequest();
    $body = $request->getBody()->__toString();

    expect($request->getMethod())->toBe('POST');
    expect($request->getUri()->__toString())->toBe('https://provider.com/oauth2/token');
    expect($request->getHeaderLine('Authorization'))->toBe(
        'Basic MmJmZTlkNzJhNGFhZThmMDZhMzEwMjViNzUzNmJlODA6OWQ2NjdjMmI3ZmFlN2EzMjlmMzJiNmRmMTc5MjYxNTQ='
    );

    expect($body)->toContain('grant_type=refresh_token');
    expect($body)->toContain('custom_param=custom_value');
});

it('should not send Authorization when there is no client secret', function () {
    $client = setup_client();
    $grant = setup_grant(RefreshToken::class, ['client_secret' => null], $client);
    $token = $grant->requestAccessToken('6c8a7d4aa21708a432174e4cb5c6cfaf0218f5f3e52f9a76a7d95d2aaade2c83');

    $request = $client->getLastRequest();

    expect($request->getHeaderLine('Authorization'))->toBe('');
});

it('should throw an exception when an access token request fails', function () {
    $response = create_response(400, [
        'error' => 'Invalid request',
    ]);

    $client = setup_client($response);
    $grant = setup_grant(RefreshToken::class, [], $client);

    $refreshToken = '6c8a7d4aa21708a432174e4cb5c6cfaf0218f5f3e52f9a76a7d95d2aaade2c83';

    expect(fn () => $grant->requestAccessToken($refreshToken))->toThrow(GrantException::class);
});

it('should throw an exception when no access token is present in response', function () {
    $response = create_response(200, [
        'access_token' => null,
    ]);

    $client = setup_client($response);
    $grant = setup_grant(RefreshToken::class, [], $client);

    $refreshToken = '6c8a7d4aa21708a432174e4cb5c6cfaf0218f5f3e52f9a76a7d95d2aaade2c83';

    expect(fn () => $grant->requestAccessToken($refreshToken))->toThrow(GrantException::class);
});
