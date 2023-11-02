<?php declare(strict_types=1);

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Route\RouteMiddleware;

readonly class InternalMiddleware extends RouteMiddleware
{
    private array $trust;

    public function __construct(array $trust)
    {
        $this->trust = $trust;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = connection_ip($request);

        if ($ip === null)
            return json_response(404, body: ['code' => 404, 'name' => Response::$codes[404]['name'], 'message' => Response::$codes[404]['message']])->withStatus(404);

        foreach ($this->trust as $item)
            if (ip_in_range($ip, $item))
                return $handler->handle($request);

        return json_response(404, body: ['code' => 404, 'name' => Response::$codes[404]['name'], 'message' => Response::$codes[404]['message']])->withStatus(404);
    }
}