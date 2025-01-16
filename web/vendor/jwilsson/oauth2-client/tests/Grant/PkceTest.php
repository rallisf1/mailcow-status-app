<?php

declare(strict_types=1);

use OAuth2\Grant\Exception\GrantException;
use OAuth2\Grant\Pkce;

beforeEach(function () {
    $this->grantOptions = [
        'endpoints' => [
            // Include a query param to test appending in getAuthorizationUrl()
            'auth_url' => 'https://provider.com/oauth2/auth?grant=pkce',
            'token_url' => 'https://provider.com/oauth2/token',
        ],
    ];
});

it('should generate a verifier', function () {
    $grant = setup_grant(Pkce::class);
    $verifier = $grant->generateVerifier(64);

    expect($verifier)->toHaveLength(64);
});

it('should throw when passed an invalid verifier length', function () {
    $grant = setup_grant(Pkce::class);

    expect(fn () => $grant->generateVerifier(16))->toThrow(\UnexpectedValueException::class);
});

it('should create an authorization url', function () {
    $grant = setup_grant(Pkce::class, $this->grantOptions);

    $state = $grant->generateState();
    $verifier = 'bb064b23223dff9b805313cdafc355acc64f1642';
    $challenge = '5FOTHWa4QWOwjDt1TV7O19MSuYxfDyjn4PwtG5WbPxg';

    $authorizationUrl = $grant->getAuthorizationUrl($state, $verifier, [
        'custom_param' => 'custom_value',
        'scope' => 'scope-1 scope-2',
    ]);

    expect($authorizationUrl)->toContain('client_id=2bfe9d72a4aae8f06a31025b7536be80');
    expect($authorizationUrl)->toContain('redirect_uri=https%3A%2F%2Fexample.com%2Fcallback');
    expect($authorizationUrl)->toContain('response_type=code');
    expect($authorizationUrl)->toContain('scope=scope-1%20scope-2');
    expect($authorizationUrl)->toContain('state=' . $state);
    expect($authorizationUrl)->toContain('https://provider.com/oauth2/auth');
    expect($authorizationUrl)->toContain('code_challenge=' . $challenge);
    expect($authorizationUrl)->toContain('code_challenge_method=S256');
    expect($authorizationUrl)->toContain('custom_param=custom_value');
});

it('should request an access token', function () {
    $client = setup_client();
    $grant = setup_grant(Pkce::class, $this->grantOptions, $client);

    $code = '5694d08a2e53ffcae0c3103e5ad6f6076abd960eb1f8a56577040bc1028f702b';
    $verifier = 'bb064b23223dff9b805313cdafc355acc64f1642';
    $token = $grant->requestAccessToken($code, $verifier, [
        'custom_param' => 'custom_value',
    ]);

    $request = $client->getLastRequest();
    $body = $request->getBody()->__toString();

    expect($request->getMethod())->toBe('POST');
    expect($request->getUri()->__toString())->toBe('https://provider.com/oauth2/token');

    expect($body)->toContain('client_id=2bfe9d72a4aae8f06a31025b7536be80');
    expect($body)->toContain('code=' . $code);
    expect($body)->toContain('code_verifier=' . $verifier);
    expect($body)->toContain('grant_type=authorization_code');
    expect($body)->toContain('redirect_uri=https%3A%2F%2Fexample.com%2Fcallback');
});

it('should throw an exception when an access token request fails', function () {
    $response = create_response(400, [
        'error' => 'Invalid request',
    ]);

    $client = setup_client($response);
    $grant = setup_grant(Pkce::class, $this->grantOptions, $client);

    $code = '5694d08a2e53ffcae0c3103e5ad6f6076abd960eb1f8a56577040bc1028f702b';
    $verifier = 'bb064b23223dff9b805313cdafc355acc64f1642';

    expect(fn () => $grant->requestAccessToken($code, $verifier))->toThrow(GrantException::class);
});

it('should throw an exception when no access token is present in response', function () {
    $response = create_response(200, [
        'access_token' => null,
    ]);

    $client = setup_client($response);
    $grant = setup_grant(Pkce::class, $this->grantOptions, $client);

    $code = '5694d08a2e53ffcae0c3103e5ad6f6076abd960eb1f8a56577040bc1028f702b';
    $verifier = 'bb064b23223dff9b805313cdafc355acc64f1642';

    expect(fn () => $grant->requestAccessToken($code, $verifier))->toThrow(GrantException::class);
});
