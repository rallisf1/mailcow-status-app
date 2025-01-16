<?php

declare(strict_types=1);

namespace OAuth2;

class Token implements \JsonSerializable
{
    private string $accessToken = '';
    private int $expires = 0;
    private string $refreshToken = '';
    private string $scope = '';
    private string $tokenType = '';
    private array $values = [];

    /**
     * Constructor, set token properties.
     *
     * @param array $parameters Values from a successful access token request.
     */
    public function __construct(array $parameters)
    {
        if (isset($parameters['access_token'])) {
            $this->accessToken = $parameters['access_token'];
        }

        // If passed an expiry time directly use that, otherwise we create it
        if (isset($parameters['expires'])) {
            $this->expires = $parameters['expires'];
        } elseif (isset($parameters['expires_in'])) {
            $this->expires = time() + $parameters['expires_in'];
        }

        if (isset($parameters['refresh_token'])) {
            $this->refreshToken = $parameters['refresh_token'];
        }

        if (isset($parameters['scope'])) {
            $this->scope = $parameters['scope'];
        }

        if (isset($parameters['token_type'])) {
            $this->tokenType = $parameters['token_type'];
        }

        $this->values = array_diff_key($parameters, [
            'access_token' => '',
            'expires_in' => '',
            'expires' => '',
            'refresh_token' => '',
            'scope' => '',
            'token_type' => '',
        ]);
    }

    /**
     * Get the access token associated with this token.
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Get the expiry time associated with this token.
     *
     * @return int
     */
    public function getExpires(): int
    {
        return $this->expires;
    }

    /**
     * Get the refresh token associated with this token.
     *
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * Get the scope associated with this token.
     *
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Get the token type associated with this token.
     *
     * @return string
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * Get the other values associated with this token.
     *
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Returns the Token data to be serialized as JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get the complete Token object as an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $parameters = $this->values;

        if ($this->accessToken) {
            $parameters['access_token'] = $this->accessToken;
        }

        if ($this->expires) {
            $parameters['expires'] = $this->expires;
        }

        if ($this->refreshToken) {
            $parameters['refresh_token'] = $this->refreshToken;
        }

        if ($this->scope) {
            $parameters['scope'] = $this->scope;
        }

        if ($this->tokenType) {
            $parameters['token_type'] = $this->tokenType;
        }

        return $parameters;
    }
}
