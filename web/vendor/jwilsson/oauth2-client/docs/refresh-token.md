# Refresh Code Grant Type
The Refresh Code Grant Type is a one step process to getting an access token using an existing refresh token obtained through a previous access token request.

```php
// auth.php

$provider = new OAuth2\Provider([
    'client_id' => 'MY_CLIENT_ID',
    'client_secret' => 'MY_CLIENT_SECRET',
    'redirect_uri' => 'MY_REDIRECT_URI',
    'endpoints' => [
        'auth_url' => 'https://provider.com/oauth2/auth',
        'token_url' => 'https://provider.com/oauth2/token',
    ],
]);

$grant = $provider->initGrant(OAuth2\Grant\RefreshToken::class);

// Fetch the refresh token from a previously requested access token
$refreshToken = $existingToken->getRefreshToken();

try {
    // $token will be an instance of OAuth2\Token
    $token = $grant->requestAccessToken($refreshToken, [
        // Additional options depending on the provider
    ]);
} catch (OAuth2\GrantException $e) {
    var_dump($e);
}
```

Read more about the available methods on [OAuth2\Token](token.md) and how to [make authenticated requests](making-authenticated-requests.md).
