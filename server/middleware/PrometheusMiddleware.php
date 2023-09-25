<?php

namespace Selpol\Middleware;

use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RedisException;
use Selpol\Service\PrometheusService;

class PrometheusMiddleware implements MiddlewareInterface
{
    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $prometheus = container(PrometheusService::class);

        $requestCount = $prometheus->getCounter('http', 'request_count', 'Http request count', ['url', 'code']);

        $response = $handler->handle($request);

        $requestCount->incBy(1, [$request->getRequestTarget(), $response->getStatusCode()]);

        return $response;
    }
}