<?php declare(strict_types=1);

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Route\RouteMiddleware;
use Selpol\Service\AuthService;
use Selpol\Service\RedisService;

readonly class RateLimitMiddleware extends RouteMiddleware
{
    private array $trust;

    private int $count;

    private int $ttl;

    private bool $request;

    private bool $null;

    public function __construct(array $config)
    {
        $this->trust = $config['trust'];

        $this->count = $config['count'];
        $this->ttl = $config['ttl'];

        $this->null = $config['null'];

        $this->request = $config['request'] ?? false;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $redis = container(RedisService::class)->getConnection();

        if (is_null($redis)) {
            if (!$this->null) {
                $ttl = 5;

                return json_response(429, body: ['code' => 429, 'name' => Response::$codes[429]['name'], 'message' => 'Слишком много запросов, пожалуйста попробуйте, через ' . $ttl . ' секунд'])
                    ->withHeader('Retry-After', [(string) $ttl]);
            }

            return $handler->handle($request);
        }

        $ip = connection_ip($request);

        foreach ($this->trust as $item) {
            if (ip_in_range($ip, $item)) {
                return $handler->handle($request);
            }
        }

        $key = 'rate:' . $ip;

        if ($token = container(AuthService::class)->getToken()) {
            $key .= ':' . $token->getIdentifierName() . '-' . $token->getIdentifier();
        }

        if ($this->request) {
            $key .= ':' . str_replace('/', '-', $request->getRequestTarget());
        }

        $value = $redis->incr($key);

        if ($value <= 1) {
            $redis->expire($key, $this->ttl);
        }

        if ($value > $this->count) {
            $ttl = $redis->ttl($key);

            return json_response(429, body: ['code' => 429, 'name' => Response::$codes[429]['name'], 'message' => 'Слишком много запросов, пожалуйста попробуйте, через ' . $ttl . ' секунд'])
                ->withHeader('Retry-After', [(string) $ttl]);
        }

        return $handler->handle($request);
    }
}