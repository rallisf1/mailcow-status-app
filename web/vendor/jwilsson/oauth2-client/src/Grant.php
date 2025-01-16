<?php

declare(strict_types=1);

namespace OAuth2;

use OAuth2\Grant\Exception\GrantException;
use OAuth2\Token;
use OAuth2\Util\QueryTrait;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

abstract class Grant
{
    use QueryTrait;

    /**
     * Constructor, set options and instantiate common classes.
     *
     * @param array $options Options for the provider to use.
     * @param ClientInterface $httpClient A PSR-18 compatible HTTP client to use.
     * @param RequestFactoryInterface $requestFactory A PSR-17 compatible request factory to use.
     * @param StreamFactoryInterface $streamFactory A PSR-17 compatible stream factory to use.
     */
    public function __construct(
        protected array $options,
        protected ClientInterface $httpClient,
        protected RequestFactoryInterface $requestFactory,
        protected StreamFactoryInterface $streamFactory
    ) {
        $this->options = $options;
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * Create an authorization URL.
     *
     * @param string $state A random, secret value used to protect aginst CSRF attacks.
     * @param array<string, mixed> $parameters Parameters to include in the authorization URL.
     *
     * @return string
     */
    protected function createAuthorizationUrl(string $state, array $parameters = []): string
    {
        $parameters = array_replace([
            'client_id' => $this->options['client_id'],
            'redirect_uri' => $this->options['redirect_uri'] ?? null,
            'response_type' => 'code',
            'scope' => null,
            'state' => $state,
        ], $parameters);

        $url = $this->options['endpoints']['auth_url'];
        $sep = str_contains($url, '?') ? '&' : '?';

        return $url . $sep . $this->buildQuery($parameters);
    }

    /**
     * Create a new token request instance.
     *
     * @param array<string, mixed> $parameters Parameters to pass to the authorization server.
     *
     * @return RequestInterface
     */
    protected function createTokenRequest(array $parameters): RequestInterface
    {
        $body = $this->buildQuery($parameters);
        $body = $this->streamFactory->createStream($body);

        $request = $this->requestFactory->createRequest('POST', $this->options['endpoints']['token_url']);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $request = $request->withHeader('Accept', 'application/json');
        $request = $request->withBody($body);

        return $request;
    }

    /**
     * Generate a random state value.
     *
     * @param int $length Length of the state value.
     *
     * @return string
     */
    public function generateState(int $length = 32): string
    {
        // Length will be doubled when converting to hex
        $length = max(1, intval($length / 2));

        return bin2hex(
            random_bytes($length)
        );
    }

    /**
     * Send a previously instantiated token request.
     *
     * @param RequestInterface $request The token request object.
     *
     * @throws GrantException
     *
     * @return Token
     */
    protected function sendTokenRequest(RequestInterface $request): Token
    {
        $response = $this->httpClient->sendRequest($request);
        $body = $response->getBody()->__toString();
        $body = (array) json_decode($body, true);

        if (isset($body['error'])) {
            $message = strval($body['error_description'] ?? $body['error']);

            throw new GrantException($message, $response->getStatusCode(), $response);
        } elseif (!isset($body['access_token'])) {
            $message = 'No access token present in response.';

            throw new GrantException($message, $response->getStatusCode(), $response);
        }

        return new Token($body);
    }
}
