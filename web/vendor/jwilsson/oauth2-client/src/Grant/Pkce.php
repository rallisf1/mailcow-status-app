<?php

declare(strict_types=1);

namespace OAuth2\Grant;

use OAuth2\Grant;
use OAuth2\Token;

class Pkce extends Grant
{
    /**
     * Create a code challenge from a code verifier.
     *
     * @param string $verifier The code verifier.
     * @param string $hashAlgo The hash algorithm to use.
     *
     * @return string
     */
    protected function createChallenge(string $verifier, string $hashAlgo = 'sha256'): string
    {
        $challenge = hash($hashAlgo, $verifier, true);
        $challenge = base64_encode($challenge);
        $challenge = strtr($challenge, '+/', '-_');
        $challenge = rtrim($challenge, '=');

        return $challenge;
    }

    /**
     * Generate a code verifier.
     *
     * @param int $length The verifier length, must be between 43 and 128 characters long.
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    public function generateVerifier(int $length = 128): string
    {
        if ($length < 43 || $length > 128) {
            throw new \UnexpectedValueException('Code verifier length must be between 43 and 128 characters.');
        }

        return $this->generateState($length);
    }

    /**
     * Get the authorization URL.
     *
     * @param string $state A random, secret value used to protect aginst CSRF attacks.
     * @param string $verifier The code verifier to generate a code challenge from.
     * @param array<string, mixed> $parameters Additional parameters to include in the authorization URL.
     *
     * @return string
     */
    public function getAuthorizationUrl(string $state, string $verifier, array $parameters = []): string
    {
        $parameters = array_replace([
            'code_challenge' => $this->createChallenge($verifier),
            'code_challenge_method' => 'S256',
        ], $parameters);

        return $this->createAuthorizationUrl($state, $parameters);
    }

    /**
     * Request an access token from the authorization server.
     *
     * @param string $code The authorization code returned from the authorization server.
     * @param string $verifier The previously created code verifier.
     * @param array<string, mixed> $parameters Additional parameters to pass to the authorization server.
     *
     * @return Token
     */
    public function requestAccessToken(string $code, string $verifier, array $parameters = []): Token
    {
        $parameters = array_replace([
            'client_id' => $this->options['client_id'],
            'code_verifier' => $verifier,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->options['redirect_uri'],
        ], $parameters);

        $request = $this->createTokenRequest($parameters);

        return $this->sendTokenRequest($request);
    }
}
