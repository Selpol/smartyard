<?php declare(strict_types=1);

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Selpol\Http\Response;
use Selpol\Service\HttpService;

class InternalMiddleware implements MiddlewareInterface
{
    private array $trust;
    private ?LoggerInterface $logger;

    public function __construct()
    {
        $this->trust = config_get('internal.trust') ?? ['127.0.0.1/32'];
        $this->logger = (config_get('internal.logger') ?? false) ? file_logger('internal') : null;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = connection_ip($request);

        if ($ip === null) {
            /** @var HttpService $http */
            $http = $request->getAttribute('http');

            $this->logger?->debug('Request without ip ' . $request->getRequestTarget(), ['ip' => $ip]);

            return $http->createResponse(404)
                ->withJson(['code' => 404, 'name' => Response::$codes[404]['name'], 'message' => Response::$codes[404]['message']]);
        }

        foreach ($this->trust as $item)
            if (ip_in_range($ip, $item)) {
                $response = $handler->handle($request);

                $this->logger?->debug('Response ' . $request->getRequestTarget(), ['ip' => $ip, 'code' => $response->getStatusCode()]);

                return $response;
            }

        /** @var HttpService $http */
        $http = $request->getAttribute('http');

        $this->logger?->debug('Request denied ' . $request->getRequestTarget(), ['ip' => $ip]);

        return $http->createResponse(404)
            ->withJson(['code' => 404, 'name' => Response::$codes[404]['name'], 'message' => Response::$codes[404]['message']]);
    }
}