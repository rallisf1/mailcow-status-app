# Proof Key for Code Exchange (PKCE) Grant Type
The PKCE grant type is very similar to the [Authorization Code](authorization-code.md) grant type, but instead of using a client secret which might not always be viable it uses a code challenge flow.

## Step 1
Just like when using the Authorization Code grant type, we start by sending the user to a authorization URL. However, when using PKCE we first need to generate a string known as a _code verifier_ and use that in the authorization url.

```php
// auth.php

$provider = new OAuth2\Provider([
    'client_id' => 'MY_CLIENT_ID',
    'redirect_uri' => 'MY_REDIRECT_URI',
    'endpoints' => [
        'auth_url' => 'https://provider.com/oauth2/auth',
        'token_url' => 'https://provider.com/oauth2/token',
    ],
]);

$grant = $provider->initGrant(OAuth2\Grant\Pkce::class);

// $state is a random value to protect aginst CSRF attacks.
$state = $grant->generateState();

// Generate a code verifier
$verifier = $grant->generateVerifier();

$authUrl = $grant->getAuthorizationUrl($state, $verifier, [
    // Additional options, for example "scope" to allow fine grained control of your app's permissions.
    // The options here will differ depending on the provider
]);

// Store the state somewhere, we'll need to verify it later when the user is redirected back to our app
$_SESSION['state'] => $state;

// Store the code verifier somewhere, we'll need to send it back to the authorization server in the next step
$_SESSION['verifier'] = $verifier;

// Redirect the user to approve the app
header('Location: ' . $authUrl);
```

## Step 2
The second step is also very similar to the Authorization Code grant type where the user is redirected back to your `redirect_uri` after approving it. Two query parameters will be included, a `code` parameter which we'll use to request an access token and a `state` parameter which will contain the same value as we previously sent. We'll need to compare this `state` value with the one we have stored to make sure it's the correct request. We'll also need to pass the _code verifier_ from the previous step back to the authorization server.

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
    'redirect_uri' => 'MY_REDIRECT_URI',
    'endpoints' => [
        'auth_url' => 'https://provider.com/oauth2/auth',
        'token_url' => 'https://provider.com/oauth2/token',
    ],
]);

$grant = $provider->initGrant(OAuth2\Grant\Pkce::class);

// Grab the previously generated code verifier
$verifier = $_SESSION['verifier'];

try {
    // $token will be an instance of OAuth2\Token
    $token = $grant->requestAccessToken($code, $verifier, [
        // Additional options depending on the provider
    ]);
} catch (OAuth2\GrantException $e) {
    var_dump($e);
}
```

Read more about the available methods on [OAuth2\Token](token.md) and how to [make authenticated requests](making-authenticated-requests.md).
