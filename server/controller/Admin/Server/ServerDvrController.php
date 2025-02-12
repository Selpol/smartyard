<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Server;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Server\ServerDvrStoreRequest;
use Selpol\Controller\Request\Admin\Server\ServerDvrUpdateRequest;
use Selpol\Controller\Request\PageRequest;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Сервер-Архивов
 */
#[Controller('/admin/server/dvr')]
readonly class ServerDvrController extends AdminRbtController
{
    /**
     * Получить список серверов архивов
     */
    #[Get]
    public function index(PageRequest $request): ResponseInterface
    {
        return self::success(DvrServer::fetchPage($request->page, $request->size, criteria()->asc('id')));
    }

    /**
     * Создание нового сервера архива
     */
    #[Post]
    public function store(ServerDvrStoreRequest $request): ResponseInterface
    {
        $dvr = new DvrServer();

        $dvr->title = $request->title;
        $dvr->type = $request->type;

        $dvr->url = $request->url;

        $dvr->token = $request->token;
        $dvr->credentials = $request->credentials;

        $dvr->insert();

        return self::success($dvr->id);
    }

    /**
     * Обновление сервера архива
     */
    #[Put('/{id}')]
    public function update(ServerDvrUpdateRequest $request): ResponseInterface
    {
        $dvr = DvrServer::findById($request->id);

        if (!$dvr) {
            return self::error('Не удалось найти сервер архива', 404);
        }

        $dvr->title = $request->title;
        $dvr->type = $request->type;

        $dvr->url = $request->url;

        $dvr->token = $request->token;

        if ($request->credentials && !str_contains($request->credentials, '*')) {
            $dvr->credentials = $request->credentials;
        }

        $dvr->update();

        return self::success();
    }

    /**
     * Удалить сервера архива
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $dvr = DvrServer::findById($id);

        if (!$dvr) {
            return self::error('Не удалось найти сервер архива', 404);
        }

        $dvr->delete();

        return self::success();
    }
}
