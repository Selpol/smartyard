<?php declare(strict_types=1);

namespace Selpol\Middleware\Admin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Repository\PermissionRepository;
use Selpol\Framework\Router\Route\Route;
use Selpol\Framework\Router\Route\RouteMiddleware;
use Selpol\Service\AuthService;
use Throwable;

readonly class ScopeMiddleware extends RouteMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $target = $request->getUri()->getPath();
        $method = strtolower($request->getMethod());

        $route = $request->getAttribute('route');

        if (!($route instanceof Route)) {
            return AdminRbtController::error('Не удалось проверить права', 403);
        }

        $paths = $route->paths;

        array_shift($paths);

        $segments = explode('/', substr($target, 7));
        $result = [];

        for ($i = 0; $i < count($paths); $i++) {
            if (str_starts_with($paths[$i], '{') && str_ends_with($paths[$i], '}')) {
                continue;
            }

            $result[] = $segments[$i];
        }

        $scope = implode('-', $result) . '-' . $route->route['class'][1] . '-' . $method;

        if (!container(AuthService::class)->checkScope($scope)) {
            try {
                $permission = container(PermissionRepository::class)->findByTitle($scope);

                return AdminRbtController::error('Не достаточно прав для действия (' . ($permission?->description ?? $scope) . ')', 403);
            } catch (Throwable) {
                return AdminRbtController::error('Не достаточно прав', 403);
            }
        }

        return $handler->handle($request);
    }
}