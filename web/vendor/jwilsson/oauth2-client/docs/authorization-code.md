# Authorization Code Grant Type
The Authorization Code Grant Type is a two step process for getting access to a resource. This is most commonly used when requesting access to a user's account information.

## Step 1
The first step is to construct an _authorization URL_ where the user will be redirected in order to approve your app. The `getAuthorizationUrl` method will help you construct the authorization URL given some options.

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

$grant = $provider->initGrant(OAuth2\Grant\AuthorizationCode::class);

// $state is a random value used to protect aginst CSRF attacks.
$state = $grant->generateState();

$authUrl = $grant->getAuthorizationUrl($state, [
    // Additional options, for example "scope" to allow fine grained control of your app's permissions.
    // The options here will differ depending on the provider
    'scope' => 'scope-1 scope-2',
]);

// Store the state somewhere, we'll need to verify it later when the user is redirected back to our app
$_SESSION['state'] => $state;

// Redirect the user to approve the app
header('Location: ' . $authUrl);
```

## Step 2
After the user has approved your app, they will be redirected back to the `redirect_uri` you specified together with a `code` query parameter which we'll use to request an access token. A `state` query parameter which will contain the same value as we previously sent will also be included. We'll need to compare this `state` value with the one we have stored to make sure it's the correct request.

```php
// callback.php

$code = $_GET['code'];
$state = $_GET['state'];

if ($state !== $_SESSION['state']) {
    // State doesn't match, we shouldn't continue
    die('State mismatch');
}

$provider = new OAuth2\Provider([
    'client_id' => 'MY_CLIENT_ID',
    'client_secret' => 'MY_CLIENT_SECRET',
    'redirect_uri' => 'MY_REDIRECT_URI',
    'endpoints' => [
        'auth_url' => 'https://provider.com/oauth2/auth',
        'token_url' => 'https://provider.com/oauth2/token',
    ],
]);

$grant = $provider->initGrant(OAuth2\Grant\AuthorizationCode::class);

try {
    // $token will be an instance of OAuth2\Token
    $token = $grant->requestAccessToken($code, [
        // Additional options depending on the provider
    ]);
} catch (OAuth2\GrantException $e) {
    var_dump($e);
}
```

Read more about the available methods on [OAuth2\Token](token.md) and how to [make authenticated requests](making-authenticated-requests.md).
