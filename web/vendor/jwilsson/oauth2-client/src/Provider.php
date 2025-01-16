<?php

declare(strict_types=1);

namespace OAuth2;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Message\Authentication\Bearer;
use OAuth2\Grant;
use OAuth2\Token;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Provider
{
    public const AUTH_URL = '';
    public const TOKEN_URL = '';

    protected array $options;
    protected ClientInterface $httpClient;
    protected RequestFactoryInterface $requestFactory;
    protected StreamFactoryInterface $streamFactory;

    /**
     * Constructor, set options and instantiate common classes.
     *
     * @param array<string, mixed> $options Options for the provider to use.
     * @param ClientInterface|null $httpClient A PSR-18 compatible HTTP client to use.
     * @param RequestFactoryInterface|null $requestFactory A PSR-17 compatible request factory to use.
     * @param StreamFactoryInterface|null $streamFactory A PSR-17 compatible stream factory to use.
     */
    public function __construct(
        array $options = [],
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        $defaults = [
            'client_id' => null,
            'client_secret' => null,
            'endpoints' => [
                'auth_url' => static::AUTH_URL,
                'token_url' => static::TOKEN_URL,
            ],
            'redirect_uri' => null,
        ];

        $this->options = array_replace_recursive($defaults, $options);
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * Retrieve an authenticated PSR-18 client instance.
     *
     * @param Token $token Token to use instead of the instance's current one.
     *
     * @return ClientInterface
     */
    public function getAuthenticatedClient(Token $token): ClientInterface
    {
        $accessToken = $token->getAccessToken();
        $authentication = new Bearer($accessToken);

        return new PluginClient($this->httpClient, [
            new AuthenticationPlugin($authentication),
        ]);
    }

    /**
     * Setup a new Grant object.
     *
     * @param string $grantClass Name of the Grant class to instantiate.
     *
     * @return Grant
     */
    public function initGrant(string $grantClass): Grant
    {
        $grantObject = new $grantClass(
            $this->options,
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory
        );

        if (!($grantObject instanceof Grant)) {
            throw new \InvalidArgumentException(
                sprintf('Class "%s" does not inherit from OAuth2\Grant', $grantClass)
            );
        }

        return $grantObject;
    }
}
