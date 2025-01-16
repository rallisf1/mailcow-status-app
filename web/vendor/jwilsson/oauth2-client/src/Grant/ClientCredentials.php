<?php

declare(strict_types=1);

namespace OAuth2\Grant;

use OAuth2\Grant;
use OAuth2\Token;
use OAuth2\Util\QueryTrait;

class ClientCredentials extends Grant
{
    use QueryTrait;

    /**
     * Request an access token from the authorization server.
     *
     * @param array<string, mixed> $parameters Additional parameters to pass to the authorization server.
     *
     * @return Token
     */
    public function requestAccessToken(array $parameters = []): Token
    {
        $parameters = array_replace([
            'grant_type' => 'client_credentials',
        ], $parameters);

        $body = $this->buildQuery($parameters);
        $body = $this->streamFactory->createStream($body);
        $headerValue = base64_encode($this->options['client_id'] . ':' . $this->options['client_secret']);

        $request = $this->createTokenRequest($parameters);
        $request = $request->withAddedHeader('Authorization', 'Basic ' . $headerValue);
        $request = $request->withBody($body);

        return $this->sendTokenRequest($request);
    }
}
