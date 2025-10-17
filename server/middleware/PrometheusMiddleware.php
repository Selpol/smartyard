<?php declare(strict_types=1);

namespace Selpol\Middleware;

use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Framework\Router\Route\Route;
use Selpol\Framework\Router\Route\RouteMiddleware;
use Selpol\Service\PrometheusService;

readonly class PrometheusMiddleware extends RouteMiddleware
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->handle($request, $handler->handle($request));
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    private function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $prometheus = container(PrometheusService::class);

        /** @var Route|null $route */
        $route = $request->getAttribute('route');

        if ($route && array_key_exists('class', $route->route)) {
            $class = $route->route['class'][0] . '@' . $route->route['class'][1];
        } else {
            $class = '_@_';
        }

        $method = $request->getMethod();

        $requestCount = $prometheus->getCounter('http', 'request_count', 'Http request count', ['class', 'method', 'code']);
        $requestBodySizeByte = $prometheus->getCounter('http', 'request_body_size_byte', 'Http request body size byte', ['class', 'method', 'code']);

        $code = $response->getStatusCode();

        $requestCount->incBy(1, [$class, $method, $code]);

        $size = $request->getBody()->getSize();

        if ($size === null) {
            $request->getBody()->rewind();

            $size = strlen($request->getBody()->getContents());
        }

        $requestBodySizeByte->incBy($size, [$class, $method, $code]);

        $responseSize = $response->getBody()->getSize();

        if ($response->getStatusCode() !== 204 && $responseSize) {
            $responseBodySizeByte = $prometheus->getCounter('http', 'response_body_size_byte', 'Http response body size byte', ['class', 'method', 'code']);
            $responseBodySizeByte->incBy($responseSize, [$class, $method, $code]);
        }

        $responseElapsed = $prometheus->getHistogram('http', 'response_elapsed', 'Http response elapsed in milliseconds', ['class', 'method', 'code'], [5, 10, 25, 50, 75, 100, 250, 500, 750, 1000]);
        $responseElapsed->observe(microtime(true) * 1000 - $_SERVER['REQUEST_TIME_FLOAT'] * 1000, [$class, $method, $code]);

        return $response;
    }
}