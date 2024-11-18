<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\InboxIndexRequest;
use Selpol\Controller\Request\Admin\InboxStoreRequest;
use Selpol\Feature\Inbox\InboxFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;

/**
 * Сообщения абонентов
 */
#[Controller('/admin/inbox')]
readonly class InboxController extends AdminRbtController
{
    /**
     * Получить список сообщения абонента
     */
    #[Get('/{id}')]
    public function index(InboxIndexRequest $request, InboxFeature $feature): ResponseInterface
    {
        if ($request->message_id) {
            $messages = $feature->getMessages($request->id, "id", $request->message_id);
        } else {
            $messages = $feature->getMessages($request->id, "dates", ["dateFrom" => $request->from ?? 0, "dateTo" => $request->to ?? time()]);
        }

        return self::success($messages ?: []);
    }

    /**
     * Отправить сообщение абоненту
     */
    #[Post('/{id}')]
    public function store(InboxStoreRequest $request, InboxFeature $feature): ResponseInterface
    {
        $msgId = $feature->sendMessage($request->id, $request->title, $request->body, $request->action);

        if ($msgId) {
            return self::success($msgId);
        }

        return self::error('Не удалось отправить сообщения', 400);
    }
}