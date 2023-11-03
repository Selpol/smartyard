<?php

namespace Selpol\Controller\Api\inbox;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Inbox\InboxFeature;

readonly class message extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        if (@$params["messageId"]) {
            $messages = container(InboxFeature::class)->getMessages($params["_id"], "id", $params["messageId"]);
        } else {
            $messages = container(InboxFeature::class)->getMessages($params["_id"], "dates", ["dateFrom" => 0, "dateTo" => time()]);
        }

        return self::success($messages ?: []);
    }

    public static function POST(array $params): ResponseInterface
    {
        $msgId = container(InboxFeature::class)->sendMessage($params["_id"], $params["title"], $params["body"], $params["action"]);

        if ($msgId)
            return self::success($msgId);

        return self::error('Не удалось отправить сообщения', 400);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Сообщения] Получить список', 'POST' => '[Сообщения] Отправить сообщение пользователю'];
    }
}
