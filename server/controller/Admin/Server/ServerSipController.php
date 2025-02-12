<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Server;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Server\ServerSipStoreRequest;
use Selpol\Controller\Request\Admin\Server\ServerSipUpdateRequest;
use Selpol\Controller\Request\PageRequest;
use Selpol\Entity\Model\Sip\SipServer;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Сервер-Сип
 */
#[Controller('/admin/server/sip')]
readonly class ServerSipController extends AdminRbtController
{
    /**
     * Получить список сип
     */
    #[Get]
    public function index(PageRequest $request): ResponseInterface
    {
        return self::success(SipServer::fetchPage($request->page, $request->size, criteria()->asc('id')));
    }

    /**
     * Создание новый сип
     */
    #[Post]
    public function store(ServerSipStoreRequest $request): ResponseInterface
    {
        $sip = new SipServer();

        $sip->title = $request->title;
        $sip->type = $request->type;

        $sip->trunk = $request->trunk;

        $sip->external_ip = $request->external_ip;
        $sip->internal_ip = $request->internal_ip;

        $sip->external_port = $request->external_port;
        $sip->internal_port = $request->internal_port;

        $sip->insert();

        return self::success($sip->id);
    }

    /**
     * Обновление сип
     */
    #[Put('/{id}')]
    public function update(ServerSipUpdateRequest $request): ResponseInterface
    {
        $sip = SipServer::findById($request->id);

        if (!$sip) {
            return self::error('Не удалось найти сип', 404);
        }

        $sip->title = $request->title;
        $sip->type = $request->type;

        $sip->trunk = $request->trunk;

        $sip->external_ip = $request->external_ip;
        $sip->internal_ip = $request->internal_ip;

        $sip->external_port = $request->external_port;
        $sip->internal_port = $request->internal_port;

        $sip->update();

        return self::success();
    }

    /**
     * Удалить сип
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $sip = SipServer::findById($id);

        if (!$sip) {
            return self::error('Не удалось найти сип', 404);
        }

        $sip->delete();

        return self::success();
    }
}
