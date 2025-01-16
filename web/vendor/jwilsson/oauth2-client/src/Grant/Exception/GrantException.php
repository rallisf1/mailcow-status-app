<?php

declare(strict_types=1);

namespace OAuth2\Grant\Exception;

use Psr\Http\Message\ResponseInterface;

class GrantException extends \Exception
{
    /**
     * @var ResponseInterface
     */
    protected ResponseInterface $response;

    /**
     * @param string $message The exception message.
     * @param int $code The exception code.
     * @param ResponseInterface $response The response that caused the exception.
     */
    public function __construct(string $message, int $code, ResponseInterface $response)
    {
        $this->response = $response;

        parent::__construct($message, $code);
    }

    /**
     * Retrieve the full response that caused the exception.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
