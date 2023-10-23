<?php declare(strict_types=1);

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Http\Response;
use Selpol\Service\HttpService;

readonly class InternalMiddleware implements MiddlewareInterface
{
    private array $trust;

    public function __construct()
    {
        $this->trust = config_get('internal.trust') ?? ['127.0.0.1/32'];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = connection_ip($request);

        if ($ip === null)
            return container(HttpService::class)->createResponse(404)
                ->withJson(['code' => 404, 'name' => Response::$codes[404]['name'], 'message' => Response::$codes[404]['message']]);

        foreach ($this->trust as $item)
            if (ip_in_range($ip, $item))
                return $handler->handle($request);

        return container(HttpService::class)->createResponse(404)
            ->withJson(['code' => 404, 'name' => Response::$codes[404]['name'], 'message' => Response::$codes[404]['message']]);
    }
}