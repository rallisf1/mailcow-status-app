<?php

declare(strict_types=1);

use Http\Mock\Client;
use OAuth2\Grant\AuthorizationCode;
use OAuth2\Provider;
use OAuth2\Token;

beforeEach(function () {
    $this->mockClient = new Client();
    $this->provider = new Provider([], $this->mockClient);
});

it('should create an authenticated client', function () {
    $token = new Token([
        'access_token' => 'c572c16299f42e07b03540d1d4410604f7e4471c7a30beeeaefa81972bc1c4ed',
    ]);

    $client = $this->provider->getAuthenticatedClient($token);
    $client->sendRequest(create_request());

    expect(
        $this->mockClient->getLastRequest()->getHeaderLine('Authorization')
    )->toBe('Bearer c572c16299f42e07b03540d1d4410604f7e4471c7a30beeeaefa81972bc1c4ed');
});

it('should init a Grant class', function () {
    $grant = $this->provider->initGrant(AuthorizationCode::class);

    expect($grant)->toBeInstanceOf(AuthorizationCode::class);
});

it('should throw when class does not extend from Grant', function () {
    expect(fn () => $this->provider->initGrant(\stdClass::class))->toThrow(\InvalidArgumentException::class);
});
