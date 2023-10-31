<?php

namespace Selpol\Runner;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Framework\Http\ServerRequest;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Router\Router;
use Selpol\Router\RouterMatch;
use Selpol\Runner\Trait\ResponseTrait;

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
        $request = http()->createServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER["REQUEST_URI"], $_SERVER);

        $request->withParsedBody(http()->getParsedBody($request->getBody(), $request->getHeader('Content-Type')));

        kernel()->getContainer()->set(ServerRequest::class, $request);

        $route = (new Router())->match($request);

        if ($route !== null) {
            $this->middlewares = $route->getMiddlewares();

            return $this->emit($this->handle($request->withAttribute('route', $route)));
        }

        return $this->emit($this->rbtResponse(404));
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
                return $this->rbtResponse(404);

            if (!class_exists($route->getClass()))
                return $this->rbtResponse(500);

            $class = $route->getClass();
            $instance = new $class($route);

            return $instance->{$route->getMethod()}();
        }

        /** @var MiddlewareInterface $middleware */
        $middleware = kernel()->getContainer()->make(array_shift($this->middlewares));

        return $middleware->process($request, $this);
    }
}