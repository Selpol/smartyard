<?php

/**
 * inbox api
 */

namespace api\inbox {

    use api\api;
    use Selpol\Feature\Inbox\InboxFeature;

    /**
     * message method
     */
    class message extends api
    {
        public static function GET($params)
        {
            if (@$params["messageId"]) {
                $messages = container(InboxFeature::class)->getMessages($params["_id"], "id", $params["messageId"]);
            } else {
                $messages = container(InboxFeature::class)->getMessages($params["_id"], "dates", ["dateFrom" => 0, "dateTo" => time()]);
            }

            return api::ANSWER($messages, ($messages !== false) ? "messages" : "notAcceptable");
        }

        public static function POST($params)
        {
            $msgId = container(InboxFeature::class)->sendMessage($params["_id"], $params["title"], $params["body"], $params["action"]);

            return api::ANSWER($msgId, ($msgId !== false) ? "$msgId" : "");
        }

        public static function index()
        {
            return [
                "GET" => "[Сообщения] Получить список",
                "POST" => "[Сообщения] Отправить сообщение пользователю",
            ];
        }
    }
}