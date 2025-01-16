# PHP OAuth2 Client Library
[![Packagist](https://img.shields.io/packagist/v/jwilsson/oauth2-client.svg)](https://packagist.org/packages/jwilsson/oauth2-client)
![build](https://github.com/jwilsson/php-oauth2-client/workflows/build/badge.svg)
[![Coverage Status](https://coveralls.io/repos/jwilsson/php-oauth2-client/badge.svg?branch=main)](https://coveralls.io/r/jwilsson/php-oauth2-client?branch=main)

## Features
* Fully supports modern OAuth2 grant types:
    * Authorization Code
    * Client Credentials
    * Proof Key for Code Exchange (PKCE)
    * Refresh Token
* Full utilization of PSR-7, PSR-17, and PSR-18.
* Compatible with PSR-4 autoloading.

## Requirements
* PHP 8.1 or later.
* A [PSR-18 HTTP client](https://packagist.org/providers/php-http/client-implementation).
* A [PSR-7 implementation](https://packagist.org/providers/psr/http-message-implementation).

## Installation
Via Composer:

```bash
composer require jwilsson/oauth2-client
```

## Usage
See the [`docs`](docs/) folder for complete usage information.

## Related
* [AutoRefreshOAuth2TokenPlugin](https://github.com/jwilsson/php-auto-refresh-oauth2-token-plugin) - A HTTPlug plugin to automatically refresh expired OAuth2 access tokens.
