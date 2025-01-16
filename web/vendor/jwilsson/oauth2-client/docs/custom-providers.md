# Custom Providers
It can be convenient to keep provider endpoint URLs in one place instead of having to pass them each time you instantiate the provider. Therefore it's possible for you to create your own provider and specifying the endpoint URLs there.

```php
// MyProvider.php
class MyProvider extends OAuth2\Provider {
    public const AUTH_URL = 'https://my-provider.com/oauth2/auth';

    public const TOKEN_URL = 'https://my-provider.com/oauth2/token';
}
```

Setting the `AUTH_URL` and `TOKEN_URL` constants is everything that's needed for them to always be used when instantiating a new `OAuth2\Provider` instance. However, if you for some reason need to override them you call still pass a `endpoints` key and it will be used instead.

```php
// auth.php
$provider = new MyProvider([
    'client_id' => 'MY_CLIENT_ID',
    'client_secret' => 'MY_CLIENT_SECRET',
    'redirect_uri' => 'MY_REDIRECT_URI',

    // Passing 'endpoints' here will override the values specified in the provider's constants
]);

$grant = $provider->initGrant(OAuth2\Grant\AuthorizationCode::class);

// Continue as usual
```
