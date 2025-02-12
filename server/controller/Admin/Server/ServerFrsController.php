<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Server;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Server\ServerStreamerFrsStoreRequest;
use Selpol\Controller\Request\Admin\Server\ServerStreamerFrsUpdateRequest;
use Selpol\Controller\Request\PageRequest;
use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Entity\Model\Server\StreamerServer;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Сервер-Лиц
 */
#[Controller('/admin/server/frs')]
readonly class ServerFrsController extends AdminRbtController
{
    /**
     * Получить список серверов лиц
     */
    #[Get]
    public function index(PageRequest $request): ResponseInterface
    {
        return self::success(FrsServer::fetchPage($request->page, $request->size, criteria()->asc('id')));
    }

    /**
     * Создание нового сервера лиц
     */
    #[Post]
    public function store(ServerStreamerFrsStoreRequest $request): ResponseInterface
    {
        $frs = new FrsServer();

        $frs->title = $request->title;
        $frs->url = $request->url;

        $frs->insert();

        return self::success($frs->id);
    }

    /**
     * Обновление сервера лиц
     */
    #[Put('/{id}')]
    public function update(ServerStreamerFrsUpdateRequest $request): ResponseInterface
    {
        $frs = FrsServer::findById($request->id);

        if (!$frs) {
            return self::error('Не удалось найти сервер лиц', 404);
        }

        $frs->title = $request->title;
        $frs->url = $request->url;

        $frs->update();

        return self::success();
    }

    /**
     * Удалить сервера лиц
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $frs = FrsServer::findById($id);

        if (!$frs) {
            return self::error('Не удалось найти сервер лиц', 404);
        }

        $frs->delete();

        return self::success();
    }
}
