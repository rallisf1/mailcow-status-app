<?php

declare(strict_types=1);

use OAuth2\Token;

beforeEach(function () {
    $this->parameters = [
        'access_token' => 'c572c16299f42e07b03540d1d4410604f7e4471c7a30beeeaefa81972bc1c4ed',
        'expires' => 1600000000,
        'refresh_token' => '6c8a7d4aa21708a432174e4cb5c6cfaf0218f5f3e52f9a76a7d95d2aaade2c83',
        'scope' => 'scope-1 scope-2',
        'token_type' => 'example',

        'custom_param' => 'custom_value',
    ];
});

it('should contain all values passed to it', function () {
    $token = new Token($this->parameters);

    expect($token->getAccessToken())->toBe('c572c16299f42e07b03540d1d4410604f7e4471c7a30beeeaefa81972bc1c4ed');
    expect($token->getExpires())->toBe(1600000000);
    expect($token->getRefreshToken())->toBe('6c8a7d4aa21708a432174e4cb5c6cfaf0218f5f3e52f9a76a7d95d2aaade2c83');
    expect($token->getScope())->toBe('scope-1 scope-2');
    expect($token->getTokenType())->toBe('example');
    expect($token->getValues())->toBe([
        'custom_param' => 'custom_value',
    ]);
});

it('should calculate expiration time from given seconds', function () {
    $token = new Token([
        'expires_in' => 3600,
    ]);

    expect($token->getExpires())->toBe(time() + 3600);
});

it('should return all data as an associative array', function () {
    $token = new Token($this->parameters);

    $this->assertEqualsCanonicalizing(
        $token->toArray(),
        $this->parameters
    );
});


it('should serialize all data to JSON', function () {
    $token = new Token($this->parameters);

    $this->assertJsonStringEqualsJsonString(
        (string) json_encode($token),
        (string) json_encode($this->parameters)
    );
});
