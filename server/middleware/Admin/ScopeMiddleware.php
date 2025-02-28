<?php declare(strict_types=1);

namespace Selpol\Middleware\Admin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\Permission;
use Selpol\Entity\Repository\PermissionRepository;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Framework\Router\Route\Route;
use Selpol\Framework\Router\Route\RouteMiddleware;
use Selpol\Service\AuthService;
use Throwable;

readonly class ScopeMiddleware extends RouteMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtolower($request->getMethod());

        $route = $request->getAttribute('route');

        if (!($route instanceof Route)) {
            return AdminRbtController::error('Не удалось проверить права', 403);
        }

        $result = [];

        $i = count($route->paths) > 1 ? 1 : 0;

        for (; $i < count($route->paths); $i++) {
            if (str_starts_with($route->paths[$i], '{') && str_ends_with($route->paths[$i], '}')) {
                continue;
            }

            $result[] = $route->paths[$i];
        }

        $scope = implode('-', $result);

        if ($result[count($result) - 1] !== $route->route['class'][1]) {
            $scope .= '-' . $route->route['class'][1];
        }

        $scope .= '-' . $method;

        if (!container(AuthService::class)->checkScope($scope)) {
            try {
                $permission = container(PermissionRepository::class)->findByTitle($scope);

                if (container(AuditFeature::class)->canAudit()) {
                    container(AuditFeature::class)->audit(strval($permission?->id ?: $scope), Permission::class, 'scope', 'Не удалось получить доступ (' . ($permission?->description ?: $scope) . ')');
                }

                return AdminRbtController::error('Не достаточно прав для действия (' . ($permission?->description ?? $scope) . ')', 403);
            } catch (Throwable) {
                if (container(AuditFeature::class)->canAudit()) {
                    container(AuditFeature::class)->audit($scope, Permission::class, 'scope', 'Не удалось получить доступ (' . $scope . ')');
                }

                return AdminRbtController::error('Не достаточно прав', 403);
            }
        }

        return $handler->handle($request);
    }
}