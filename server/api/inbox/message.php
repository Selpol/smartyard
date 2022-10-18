<?php

    /**
     * inbox api
     */

    namespace api\inbox {

        use api\api;

        /**
         * message method
         */

        class message extends api {

            public static function GET($params) {
                $inbox = loadBackend("inbox");

                if (@$params["messageId"]) {
                    $messages = $inbox->getMessages($params["_id"], "id", $params["messageId"]);
                } else {
                    $messages = $inbox->getMessages($params["_id"], "dates", [ "dateFrom" => "0000-00-00 00:00:00.000", "dateTo" => $params["_db"]->now() ]);
                }

                return api::ANSWER($messages, ($messages !== false)?"messages":"notAcceptable");
            }

            public static function POST($params) {
                $inbox = loadBackend("inbox");

                $msgId = $inbox->sendMessage($params["_id"], $params["title"], $params["body"], $params["action"]);

                return api::ANSWER($msgId, ($msgId !== false)?"$msgId":"");
            }

            public static function index() {
                return [
                    "GET",
                    "POST",
                ];
            }
        }
    }
