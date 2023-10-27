<?php

namespace Selpol\Controller\Api\inbox;

use Selpol\Controller\Api\Api;
use Selpol\Feature\Inbox\InboxFeature;

readonly class message extends Api
{
    public static function GET(array $params): array
    {
        if (@$params["messageId"]) {
            $messages = container(InboxFeature::class)->getMessages($params["_id"], "id", $params["messageId"]);
        } else {
            $messages = container(InboxFeature::class)->getMessages($params["_id"], "dates", ["dateFrom" => 0, "dateTo" => time()]);
        }

        return Api::ANSWER($messages, ($messages !== false) ? "messages" : "notAcceptable");
    }

    public static function POST(array $params): array
    {
        $msgId = container(InboxFeature::class)->sendMessage($params["_id"], $params["title"], $params["body"], $params["action"]);

        return Api::ANSWER($msgId, ($msgId !== false) ? "$msgId" : "");
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Сообщения] Получить список', 'POST' => '[Сообщения] Отправить сообщение пользователю'];
    }
}
