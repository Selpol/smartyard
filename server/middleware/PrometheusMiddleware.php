<?php declare(strict_types=1);

namespace Selpol\Middleware;

use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RedisException;
use Selpol\Service\PrometheusService;

readonly class PrometheusMiddleware implements MiddlewareInterface
{
    /**
     * @throws NotFoundExceptionInterface|RedisException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $this->processResponse($request, $response);

        return $response;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    private function processResponse(ServerRequestInterface $request, ResponseInterface $response): void
    {
        $prometheus = container(PrometheusService::class);

        $target = $request->getRequestTarget();
        $method = $request->getMethod();

        $requestCount = $prometheus->getCounter('http', 'request_count', 'Http request count', ['url', 'method', 'code']);
        $requestBodySizeByte = $prometheus->getCounter('http', 'request_body_size_byte', 'Http request body size byte', ['url', 'method', 'code']);

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

        $responseElapsed = $prometheus->getHistogram('http', 'response_elapsed', 'Http response elapsed in milliseconds', ['url', 'method', 'code'], [5, 10, 25, 50, 75, 100, 250, 500, 750, 1000]);
        $responseElapsed->observe(microtime(true) * 1000 - $_SERVER['REQUEST_TIME_FLOAT'] * 1000, [$target, $method, $code]);
    }
}