<?php

declare(strict_types=1);

namespace OAuth2\Grant;

use OAuth2\Grant;
use OAuth2\Token;

class AuthorizationCode extends Grant
{
    /**
     * Get the authorization URL.
     *
     * @param string $state A random, secret value used to protect aginst CSRF attacks.
     * @param array<string, mixed> $parameters Parameters to include in the authorization URL.
     *
     * @return string
     */
    public function getAuthorizationUrl(string $state, array $parameters = []): string
    {
        return $this->createAuthorizationUrl($state, $parameters);
    }

    /**
     * Request an access token from the authorization server.
     *
     * @param string $code The authorization code returned from the authorization server.
     * @param array<string, mixed> $parameters Additional parameters to pass to the authorization server.
     *
     * @return Token
     */
    public function requestAccessToken(string $code, array $parameters = []): Token
    {
        $parameters = array_replace([
            'client_id' => $this->options['client_id'],
            'client_secret' => $this->options['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->options['redirect_uri'],
        ], $parameters);

        $request = $this->createTokenRequest($parameters);

        return $this->sendTokenRequest($request);
    }
}
