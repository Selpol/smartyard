<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\User;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Entity\Model\Core\CoreAuth;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Пользователь сессия
 */
#[Controller('/admin/user/session')]
readonly class UserSessionController extends AdminRbtController
{
    /**
     * Получить список сессий пользователя
     * 
     * @param int $id Идентификатор пользователя
     */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        if ($id == 0 && $this->getUser()->getIdentifier() != 0) {
            return self::error('Только главный аккаунт может получить доступ к своим сессиям', 400);
        }

        $user = CoreUser::findById($id);

        if (!$user) {
            return self::error('Не удалось найти пользователя', 404);
        }

        return self::success($user->auths()->fetchAll(criteria()->equal('status', 1)));
    }

    /**
     * Отключить сессию пользователя
     * 
     * @param int $id Идентификатор сессии
     */
    #[Put('/{id}')]
    public function update(int $id): ResponseInterface
    {
        $auth = CoreAuth::findById($id);

        if ($auth->user_id == 0 && $this->getUser()->getIdentifier() != 0) {
            return self::error('Только главный аккаунт может получить доступ к своим сессиям', 400);
        }

        $auth->status = 0;

        $auth->update();

        return self::success();
    }
}