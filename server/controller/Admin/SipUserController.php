<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Sip\SipUserIndexRequest;
use Selpol\Controller\Request\Admin\Sip\SipUserStoreRequest;
use Selpol\Controller\Request\Admin\Sip\SipUserUpdateRequest;
use Selpol\Entity\Model\Sip\SipUser;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Sip user
 */
#[Controller('/admin/sip/user')]
readonly class SipUserController extends AdminRbtController
{
    /**
     * Получить список аккаунтов
     */
    #[Get]
    public function index(SipUserIndexRequest $request): ResponseInterface
    {
        return self::success(SipUser::fetchPage(
            $request->page,
            $request->size,
            criteria()->orEqual('type', $request->type)->orLike('title', $request->title)->asc('id')
        ));
    }

    /**
     * Создать новый аккаунт
     */
    #[Post]
    public function store(SipUserStoreRequest $request): ResponseInterface
    {
        $sipUser = new SipUser();

        $sipUser->type = $request->type;
        $sipUser->title = $request->title;

        $sipUser->password = $request->password;

        if ($sipUser->safeInsert()) {
            return self::success($sipUser->id);
        }

        return self::error('Не удалось создать сип аккаунт');
    }

    /**
     * Обновить аккаунт
     */
    #[Put('/{id}')]
    public function update(SipUserUpdateRequest $request): ResponseInterface
    {
        $sipUser = SipUser::findById($request->id, setting: setting()->nonNullable());

        $sipUser->type = $request->type;
        $sipUser->title = $request->title;

        $sipUser->password = $request->password;

        if ($sipUser->safeUpdate()) {
            return self::success($sipUser->id);
        }

        return self::error('Не удалось обновить сип аккаунт', 400);
    }

    /**
     * Удалить аккаунт
     * @param int $id Идентификатор аккаунта
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $sipUser = SipUser::findById($id, setting: setting()->nonNullable());

        if ($sipUser->safeDelete()) {
            return self::success();
        }

        return self::error('Не удалось удалить сип аккаунт', 400);
    }
}