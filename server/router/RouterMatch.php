<?php declare(strict_types=1);

namespace Selpol\Router;

use Selpol\Framework\Http\ServerRequest;
use Selpol\Validator\Exception\ValidatorException;

readonly class RouterMatch
{
    private ServerRequest $request;

    private string $class;
    private string $method;

    private array $params;

    private array $middlewares;

    public function __construct(ServerRequest $request, string $class, string $method, array $params, array $middlewares)
    {
        $this->request = $request;

        $this->class = $class;
        $this->method = $method;

        $this->params = $params;

        $this->middlewares = $middlewares;
    }

    public function getRequest(): ServerRequest
    {
        return $this->request;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getParam(string $key): ?string
    {
        return array_key_exists($key, $this->params) ? $this->params[$key] : null;
    }

    public function getQuery(string $key): ?string
    {
        $query = $this->request->getQueryParams();

        return array_key_exists($key, $query) ? $query[$key] : null;
    }

    /**
     * @throws ValidatorException
     */
    public function getParamIdOrThrow(string $key): int
    {
        return intval(rule()->id()->onItem($key, $this->params));
    }

    /**
     * @throws ValidatorException
     */
    public function getParamIntOrThrow(string $key): int
    {
        return intval(rule()->int()->onItem($key, $this->params));
    }
}