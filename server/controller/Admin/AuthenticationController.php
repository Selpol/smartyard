<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\AuthenticationRequest;
use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Middleware\Admin\AuthMiddleware;
use Selpol\Middleware\Admin\ScopeMiddleware;
use Selpol\Service\AuthService;

/**
 * Авторизация
 */
#[Controller('/admin/authentication')]
readonly class AuthenticationController extends AdminRbtController
{
    /**
     * Получить перечень прав пользователя
     */
    #[Get]
    public function index(AuthService $service): ResponseInterface
    {
        return self::success($service->getPermissions());
    }

    /**
     * Авторизация из-под пользователя
     */
    #[Post(excludes: [AuthMiddleware::class, ScopeMiddleware::class])]
    public function store(AuthenticationRequest $request, AuthenticationFeature $feature): ResponseInterface
    {
        $auth = $feature->login(
            $request->login,
            $request->password,
            $request->remember_me && $request->user_agent && $request->did,
            trim($request->user_agent ?? ''),
            trim($request->did ?? ''),
            connection_ip($request->getRequest())
        );

        if ($auth['result']) {
            return self::success($auth['token']);
        }

        return self::error(array_key_exists('message', $auth) ? $auth['message'] : 'Не удалось войти', 401);
    }

    /**
     * Выход из-под пользователя
     */
    #[Put]
    public function update(AuthenticationFeature $feature)
    {
        $token = $this->getToken();

        $feature->logout($token->getOriginalValue());

        return self::success();
    }
}