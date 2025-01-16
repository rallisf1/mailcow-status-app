<?php

declare(strict_types=1);

use OAuth2\Grant\Exception\GrantException;

it('should contain the full response that caused the exception', function () {
    $response = create_response(400);
    $exception = new GrantException('Invalid request', 400, $response);

    expect($exception->getResponse())->toBe($response);
});
