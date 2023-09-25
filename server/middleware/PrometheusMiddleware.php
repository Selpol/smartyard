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

        $requestCount = $prometheus->getCounter('http', 'request_count', 'Http request count', ['url']);
        $requestBodySizeByte = $prometheus->getCounter('http', 'request_body_size_byte', 'Http request body size byte');

        $response = $handler->handle($request);

        $requestCount->incBy(1, [$request->getRequestTarget()]);
        $requestBodySizeByte->incBy($request->getBody()->getSize() ?? 0, [$request->getRequestTarget()]);

        if ($response->getStatusCode() !== 204) {
            $responseBodySizeByte = $prometheus->getCounter('http', 'response_body_size_byte', 'Http response body size byte', ['url', 'code']);
            $responseBodySizeByte->incBy($response->getBody()->getSize(), [$request->getRequestTarget(), $response->getStatusCode()]);
        }

        return $response;
    }
}