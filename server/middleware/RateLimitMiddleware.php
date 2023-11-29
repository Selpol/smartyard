<?php declare(strict_types=1);

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RedisException;
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

    public function __construct(array $config)
    {
        $this->trust = $config['trust'];

        $this->count = $config['count'];
        $this->ttl = $config['ttl'];

        $this->request = $config['request'] ?? false;
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

        $key = 'rate:' . $ip;

        if ($token = container(AuthService::class)->getToken())
            $key .= ':' . $token->getIdentifierName() . '-' . $token->getIdentifier();

        if ($this->request)
            $key .= ':' . str_replace('/', '-', $request->getRequestTarget());

        if (!$redis->exists($key)) {
            $redis->incr($key);
            $redis->expire($key, $this->ttl);
        } else {
            $value = $redis->incr($key);

            if ($value > $this->count) {
                $ttl = $redis->ttl($key);

                return json_response(429, body: ['code' => 429, 'name' => Response::$codes[429]['name'], 'message' => 'Слишком много запросов, пожалуйста попробуйте, через ' . $ttl . ' секунд'])
                    ->withHeader('Retry-After', $ttl);
            }
        }

        return $handler->handle($request);
    }
}