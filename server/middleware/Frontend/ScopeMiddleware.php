<?php declare(strict_types=1);

namespace Selpol\Middleware\Frontend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Entity\Repository\PermissionRepository;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Route\RouteMiddleware;
use Selpol\Service\AuthService;

readonly class ScopeMiddleware extends RouteMiddleware
{
    /**
     * @var string[]
     */
    private array $scopes;

    public function __construct(array $config)
    {
        $this->scopes = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = container(AuthService::class);

        foreach ($this->scopes as $scope)
            if (!$auth->checkScope($scope)) {
                $permission = container(PermissionRepository::class)->findByTitle($scope);

                return json_response(
                    403,
                    body: [
                        'code' => 403,
                        'name' => Response::$codes[403]['name'],
                        'message' => 'Недостаточно прав для данного действия (' . ($permission?->description ?? 'Неизвестное правило') . ')'
                    ]
                );
            }

        return $handler->handle($request);
    }
}