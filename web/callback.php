<?php

session_start();

require __DIR__.'/vendor/autoload.php';

define("APP_ROOT", in_array(basename(__DIR__), ['web', 'htdocs', 'public_html', 'www']) ? '/' : '/' . basename(__DIR__) . '/');

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$code = $_GET['code'];
$state = $_GET['state'];

if ($state !== @$_SESSION['state']) {
    // State doesn't match, we shouldn't continue
    die('State mismatch');
}

$provider = new OAuth2\Provider([
    'client_id' => $_ENV['MAILCOW_CLIENT_ID'],
    'client_secret' => $_ENV['MAILCOW_CLIENT_SECRET'],
    'endpoints' => [
        'auth_url' => 'https://'.$_ENV['MAILCOW_DOMAIN'].'/oauth/authorize',
        'token_url' => 'https://'.$_ENV['MAILCOW_DOMAIN'].'/oauth/token',
    ],
]);

$grant = $provider->initGrant(OAuth2\Grant\AuthorizationCode::class);

try {
    // $token will be an instance of OAuth2\Token
    $token = $grant->requestAccessToken($code, [
        // Additional options depending on the provider
    ]);
    
    session_regenerate_id();

    $_SESSION['token'] = $token->getAccessToken();
    $_SESSION['expires'] = $token->getExpires();
    header('Location: ' . APP_ROOT . '?task=monitor');

} catch (OAuth2\GrantException $e) {
    var_dump($e);
}