<?php

declare(strict_types=1);

namespace OAuth2\Util;

trait QueryTrait
{
    /**
     * Create a query string from an array.
     *
     * @param array<string, mixed> $parameters Parameters to encode.
     *
     * @return string
     */
    protected function buildQuery(array $parameters)
    {
        return http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }
}
