<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\User;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\User\UserStoreRequest;
use Selpol\Controller\Request\Admin\User\UserUpdateRequest;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Feature\Oauth\OauthFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Service\AuthService;
use Throwable;

/**
 * Пользователь
 */
#[Controller('/admin/user')]
readonly class UserController extends AdminRbtController
{
    /**
     * Получить список пользователей
     */
    #[Get]
    public function index(AuthService $service): ResponseInterface
    {
        $criteria = $this->getUser()->getIdentifier() != 0 ? criteria()->nonEqual('uid', 0) : null;

        $users = CoreUser::fetchAll($criteria, setting()->columns(['uid', 'login', 'enabled', 'aud_jti', 'real_name', 'e_mail', 'last_login']));

        return self::success($users);
    }

    /**
     * Получить пользователя
     * 
     * @param int $id Идентификатор пользователя
     */
    #[Get('/{id}')]
    public function show(int $id, AuthService $service): ResponseInterface
    {
        if ($id == 0 && $this->getUser()->getIdentifier() != 0) {
            return self::error('Главный аккаунт можно получить только с него самого', 400);
        }

        $user = CoreUser::findById($id, setting: setting()->columns(['uid', 'login', 'enabled', 'aud_jti', 'phone', 'real_name', 'e_mail', 'last_login']));

        if (!$user) {
            return self::error('Не удалось найти пользователя', 404);
        }

        if (!$service->checkScope('mobile-mask')) {
            $user->phone = mobile_mask($user->phone);
        }

        return self::success($user);
    }

    /**
     * Создать нового пользователя
     */
    #[Post]
    public function store(UserStoreRequest $request, OauthFeature $feature)
    {
        $user = new CoreUser();

        $user->login = $request->login;
        $user->password = password_hash($request->password, PASSWORD_DEFAULT);

        $user->real_name = $request->name;
        $user->e_mail = $request->email;

        $user->phone = $request->phone;

        $user->enabled = $request->enabled;

        try {
            $user->aud_jti = $feature->register($request->phone);
        } catch (Throwable) {
        }

        $user->insert();

        return self::success($user->uid);
    }

    /**
     * Обновить пользователя
     */
    #[Put('/{id}')]
    public function update(UserUpdateRequest $request, OauthFeature $feature): ResponseInterface
    {
        if ($request->id == 0 && $this->getUser()->getIdentifier() != 0) {
            return self::error('Главный аккаунт можно изменять только с него самого', 400);
        }

        $user = CoreUser::findById($request->id);

        if (!$user) {
            return self::error('Не удалось найти пользователя', 404);
        }

        $user->login = $request->login;

        if ($request->password) {
            $user->password = password_hash($request->password, PASSWORD_DEFAULT);
        }

        $user->real_name = $request->name;
        $user->e_mail = $request->email;

        if (!str_contains($request->phone, '*')) {
            $user->phone = $request->phone;
        }

        $user->enabled = $request->enabled;

        try {
            $user->aud_jti = $feature->register($request->phone);
        } catch (Throwable) {
        }

        $user->update();

        return self::success();
    }

    /**
     * Удалить пользователя
     * 
     * @param int $id Идентификатор пользователя
     */
    #[Delete('/{id}')]
    public function delete(int $id)
    {
        if ($id == 0) {
            return self::error('Нельзя удалить главный аккаунт, только отключить', 400);
        }

        $user = CoreUser::findById($id);

        if (!$user) {
            return self::error('Не удалось найти пользователя', 404);
        }

        $user->delete();

        return self::success();
    }
}