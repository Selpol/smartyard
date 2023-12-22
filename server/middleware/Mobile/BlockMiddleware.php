<?php declare(strict_types=1);

namespace Selpol\Middleware\Mobile;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Framework\Router\Route\RouteMiddleware;
use Selpol\Service\AuthService;

readonly class BlockMiddleware extends RouteMiddleware
{
    /**
     * @var int[]
     */
    private array $services;

    public function __construct(array $config)
    {
        $this->services = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($block = container(BlockFeature::class)->getFirstBlockForSubscriber(container(AuthService::class)->getUserOrThrow()->getIdentifier(), $this->services))
            return json_response(403, body: ['code' => 403, 'message' => 'Сервис не доступен по причине блокировки.' . ($block->cause ? (' ' . $block->cause) : '')]);

        return $handler->handle($request);
    }
}