<?php declare(strict_types=1);

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
        $target = $request->getRequestTarget();
        $method = $request->getMethod();

        $prometheus = container(PrometheusService::class);

        $requestCount = $prometheus->getCounter('http', 'request_count', 'Http request count', ['url', 'method', 'code']);
        $requestBodySizeByte = $prometheus->getCounter('http', 'request_body_size_byte', 'Http request body size byte', ['url', 'method', 'code']);

        $response = $handler->handle($request);

        $code = $response->getStatusCode();

        $requestCount->incBy(1, [$target, $method, $code]);

        $size = $request->getBody()->getSize();

        if ($size === null) {
            $request->getBody()->rewind();

            $size = strlen($request->getBody()->getContents());
        }

        $requestBodySizeByte->incBy($size, [$target, $method, $code]);

        if ($response->getStatusCode() !== 204) {
            $responseBodySizeByte = $prometheus->getCounter('http', 'response_body_size_byte', 'Http response body size byte', ['url', 'method', 'code']);
            $responseBodySizeByte->incBy($response->getBody()->getSize(), [$target, $method, $code]);
        }

        return $response;
    }
}