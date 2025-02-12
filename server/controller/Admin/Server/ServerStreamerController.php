<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Server;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Server\ServerStreamerStoreRequest;
use Selpol\Controller\Request\Admin\Server\ServerStreamerUpdateRequest;
use Selpol\Controller\Request\PageRequest;
use Selpol\Entity\Model\Server\StreamerServer;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Сервер-Переменные
 */
#[Controller('/admin/server/streamer')]
readonly class ServerStreamerController extends AdminRbtController
{
    /**
     * Получить список стримеров
     */
    #[Get]
    public function index(PageRequest $request): ResponseInterface
    {
        return self::success(StreamerServer::fetchPage($request->page, $request->size, criteria()->asc('id')));
    }

    /**
     * Создание нового стримера
     */
    #[Post]
    public function store(ServerStreamerStoreRequest $request): ResponseInterface
    {
        $streamer = new StreamerServer();

        $streamer->title = $request->title;
        $streamer->url = $request->url;

        $streamer->insert();

        return self::success($streamer->id);
    }

    /**
     * Обновление стримера
     */
    #[Put('/{id}')]
    public function update(ServerStreamerUpdateRequest $request): ResponseInterface
    {
        $streamer = StreamerServer::findById($request->id);

        if (!$streamer) {
            return self::error('Не удалось найти стример', 404);
        }

        $streamer->title = $request->title;
        $streamer->url = $request->url;

        $streamer->update();

        return self::success();
    }

    /**
     * Удалить стример
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $streamer = StreamerServer::findById($id);

        if (!$streamer) {
            return self::error('Не удалось найти стример', 404);
        }

        $streamer->delete();

        return self::success();
    }
}
