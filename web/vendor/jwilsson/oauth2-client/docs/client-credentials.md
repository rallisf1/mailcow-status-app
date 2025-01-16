# Client Credentials Grant Type
The Client Credentials Grant Type is a one step process to getting an access token using only a client ID and secret. This is most commonly used when requesting access to a resource that is not tied to a specific person.

```php
// auth.php

$provider = new OAuth2\Provider([
    'client_id' => 'MY_CLIENT_ID',
    'client_secret' => 'MY_CLIENT_SECRET',
    'endpoints' => [
        'auth_url' => 'https://provider.com/oauth2/auth',
        'token_url' => 'https://provider.com/oauth2/token',
    ],
]);

$grant = $provider->initGrant(OAuth2\Grant\ClientCredentials::class);

try {
    // $token will be an instance of OAuth2\Token
    $token = $grant->requestAccessToken([
        // Additional options depending on the provider
    ]);
} catch (OAuth2\GrantException $e) {
    var_dump($e);
}
```

Read more about the available methods on [OAuth2\Token](token.md) and how to [make authenticated requests](making-authenticated-requests.md).
