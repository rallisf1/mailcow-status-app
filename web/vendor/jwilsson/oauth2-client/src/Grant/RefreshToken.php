<?php

declare(strict_types=1);

namespace OAuth2\Grant;

use OAuth2\Grant;
use OAuth2\Token;
use OAuth2\Util\QueryTrait;

class RefreshToken extends Grant
{
    use QueryTrait;

    /**
     * Request an access token from the authorization server.
     *
     * @param string $refreshToken A previously received refresh token.
     * @param array<string, mixed> $parameters Additional parameters to pass to the authorization server.
     *
     * @return Token
     */
    public function requestAccessToken(string $refreshToken, array $parameters = []): Token
    {
        $parameters = array_replace([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ], $parameters);

        $request = $this->createTokenRequest($parameters);

        if (isset($this->options['client_secret'])) {
            $headerValue = base64_encode($this->options['client_id'] . ':' . $this->options['client_secret']);

            $request = $request->withAddedHeader('Authorization', 'Basic ' . $headerValue);
        }

        return $this->sendTokenRequest($request);
    }
}
