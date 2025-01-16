# Making Authenticated Requests
After you've successfully retrieved an access token it's time to make some calls to the service you've authenticated against.

Each provider has a `getAuthenticatedClient` method which when passed a [`OAuth2\Token`](token.md) object will return a PSR-18 compatible client object authenticated and ready.

```php
$provider = new OAuth2\Provider(
    []
);

$client = $provider->getAuthenticatedClient($token); // $client will be an object implementing the Psr\Http\Client\ClientInterface interface
```

By default, [PHP-HTTP PSR-18 HTTP Client Discovery](http://docs.php-http.org/en/latest/discovery.html#psr-18-client-discovery) will be used to find a compatible PSR-18 HTTP Client. If you wish to override this and supply a PSR-18 compatible client yourself, pass it as the second argument to `OAuth2\Provider`.

```php
$provider = new OAuth2\Provider(
    [],
    $myClient
);

$client = $provider->getAuthenticatedClient($token); // The passed $myClient object decorated with authentication headers
```
