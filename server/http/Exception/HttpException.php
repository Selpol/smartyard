<?php declare(strict_types=1);

namespace Selpol\Http\Exception;

use RuntimeException;
use Selpol\Http\Request;
use Selpol\Http\Response;
use Throwable;

class HttpException extends RuntimeException
{
    private ?Request $request;
    private ?Response $response;

    public function __construct(?Request $request = null, ?Response $response = null, string $message = "", int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->request = $request;
        $this->response = $response;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }
}