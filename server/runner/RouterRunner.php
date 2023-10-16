<?php

namespace Selpol\Runner;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Http\ServerRequest;
use Selpol\Router\Router;
use Selpol\Router\RouterMatch;
use Selpol\Runner\Trait\ResponseTrait;
use Selpol\Service\HttpService;

class RouterRunner implements RunnerInterface, RunnerExceptionHandlerInterface, RequestHandlerInterface
{
    use LoggerKernelTrait;
    use ResponseTrait;

    /** @var string[] $middlewares */
    private array $middlewares = [];

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    function run(array $arguments): int
    {
        $http = container(HttpService::class);

        $request = $http->createServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER["REQUEST_URI"], $_SERVER);

        kernel()->getContainer()->set(ServerRequest::class, $request);

        $route = (new Router())->match($request);

        if ($route !== null) {
            $this->middlewares = $route->getMiddlewares();

            return $this->emit($this->handle($request->withAttribute('route', $route)));
        }

        return $this->emit($this->response(404)->withStatusJson());
    }

    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (count($this->middlewares) === 0) {
            /** @var RouterMatch $route */
            $route = $request->getAttribute('route');

            if ($route === null)
                return $this->response(404)->withStatusJson();

            if (!class_exists($route->getClass()))
                return $this->response(500)->withStatusJson();

            $class = $route->getClass();
            $instance = new $class($request);

            return $instance->{$route->getMethod()}($request);
        }

        /** @var MiddlewareInterface $middleware */
        $middleware = kernel()->getContainer()->make(array_shift($this->middlewares));

        return $middleware->process($request, $this);
    }
}