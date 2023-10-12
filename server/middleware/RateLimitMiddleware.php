<?php declare(strict_types=1);

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RedisException;
use Selpol\Http\Response;
use Selpol\Service\AuthService;
use Selpol\Service\HttpService;
use Selpol\Service\RedisService;

class RateLimitMiddleware implements MiddlewareInterface
{
    private array $trust;

    public function __construct()
    {
        $this->trust = config_get('mobile.trust') ?? ['127.0.0.1/32'];
    }

    /**
     * @throws RedisException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = connection_ip($request);

        foreach ($this->trust as $item)
            if (ip_in_range($ip, $item))
                return $handler->handle($request);

        $redis = container(RedisService::class)->getConnection();
        $token = container(AuthService::class)->getToken();

        $key = 'rate-' . $ip . ($token ? ('-' . $token->getIdentifierName() . '-' . $token->getIdentifier()) : '');

        if (!$redis->exists($key)) {
            $redis->incr($key);
            $redis->expire($key, 60);
        } else {
            $value = $redis->incr($key);

            if ($value > 60) {
                $http = container(HttpService::class);

                $ttl = $redis->ttl($key);

                return $http->createResponse(429)
                    ->withHeader('Retry-After', $ttl)
                    ->withJson(['code' => 429, 'name' => Response::$codes[429]['name'], 'message' => 'Слишком много запросов, пожалуйста попробуйте, через ' . $ttl . ' секунд']);
            }
        }

        return $handler->handle($request);
    }
}