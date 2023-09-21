<?php

namespace backends\inbox;

class clickhouse extends inbox
{
    public function sendMessage(int $subscriberId, $title, $msg, $action = "inbox"): string|bool
    {
        $subscriber = $this->db->get("select id, platform, push_token, push_token_type from houses_subscribers_mobile where house_subscriber_id = :house_subscriber_id", [
            "house_subscriber_id" => $subscriberId,
        ], [
            "id" => "id",
            "platform" => "platform",
            "push_token" => "token",
            "push_token_type" => "tokenType"
        ], ["singlify"]);

        if (!is_int($subscriber["platform"]) || !is_int($subscriber["tokenType"]) || !$subscriber["id"] || !$subscriber["token"]) {
            last_error("mobileSubscriberNotRegistered");
            return false;
        }

        if ($subscriber) {
            $msgId = $this->db->insert("insert into inbox (id, house_subscriber_id, date, title, msg, action, expire, delivered, readed, code) values (:id, :house_subscriber_id, :date, :title, :msg, :action, :expire, 0, 0, null)", [
                "id" => $subscriber["id"],
                "house_subscriber_id" => $subscriberId,
                "date" => time(),
                "title" => $title,
                "msg" => $msg,
                "action" => $action,
                "expire" => time() + 7 * 60 * 60 * 60,
            ]);

            $unreaded = $this->unreaded($subscriberId);

            if (!$msgId) {
                last_error("cantStoreMessage");
                return false;
            }

            $isdn = backend("isdn");
            if ($isdn) {
                $result = $isdn->message([
                    "token" => $subscriber["token"],
                    "type" => $subscriber["tokenType"],
                    "timestamp" => time(),
                    "ttl" => 30,
                    "platform" => (int)$subscriber["platform"] ? "ios" : "android",
                    "title" => $title,
                    "msg" => $msg,
                    "badge" => $unreaded,
                    "sound" => "default",
                    "pushAction" => $action,
                ]);

                $result = explode(":", $result);
                if ($this->db->modify("update inbox set code = :code, push_message_id = :push_message_id where msg_id = :msg_id", [
                    "msg_id" => $msgId,
                    "code" => $result[0],
                    "push_message_id" => $result[0] ?: "unknown",
                ])) {
                    return $msgId;
                } else {
                    last_error("errorSendingPush: " . $result[1] ? $result[1] : $result[0]);
                    return false;
                }
            } else {
                last_error("pushCantBeSent");
                return false;
            }
        } else {
            last_error("subscriberNotFound");
            return false;
        }
    }

    public function getMessages(int $subscriberId, $by, $params): array|bool
    {
        $w = "";
        $q = [];
        switch ($by) {
            case "dates":
                $w = "where house_subscriber_id = :id and date < :date_to and date >= :date_from";
                $q = [
                    "id" => $subscriberId,
                    "date_from" => $params["dateFrom"],
                    "date_to" => $params["dateTo"],
                ];
                break;
            case "id":
                $w = "where house_subscriber_id = :id and msg_id = :msg_id";
                $q = [
                    "id" => $subscriberId,
                    "msg_id" => $params,
                ];
                break;
            case "all":
                $w = "where house_subscriber_id = :id";
                $q = ["id" => $subscriberId];
                break;
        }

        return $this->db->get("select * from inbox $w", $q, [
            "msg_id" => "msgId",
            "house_subscriber_id" => "subscriberId",
            "id" => "id",
            "date" => "date",
            "title" => "title",
            "msg" => "msg",
            "action" => "action",
            "expire" => "expire",
            "push_message_id" => "pushMessageId",
            "delivered" => "delivered",
            "readed" => "readed",
            "code" => "code",
        ]);
    }

    public function msgMonths(int $subscriberId): array
    {
        $months = $this->db->get("select month from (select substr(date, 1, 7) as month from inbox where house_subscriber_id = :house_subscriber_id) group by month order by month", [
            "house_subscriber_id" => $subscriberId,
        ]);

        $r = [];

        foreach ($months as $month) {
            $r[] = $month["month"];
        }

        return $r;
    }

    public function markMessageAsReaded(int $subscriberId, int|bool $msgId = false): bool|int
    {
        if ($msgId) {
            return $this->db->modify("update inbox set readed = 1 where readed = 0 and msg_id = :msg_id and house_subscriber_id = :house_subscriber_id", [
                "house_subscriber_id" => $subscriberId,
                "msg_id" => $msgId,
            ]);
        } else {
            return $this->db->modify("update inbox set readed = 1 where readed = 0 and house_subscriber_id = :house_subscriber_id", [
                "house_subscriber_id" => $subscriberId,
            ]);
        }
    }

    public function markMessageAsDelivered(int $subscriberId, int|bool $msgId = false): bool|int
    {
        if ($msgId) {
            return $this->db->modify("update inbox set delivered = 1 where delivered = 0 and msg_id = :msg_id and house_subscriber_id = :house_subscriber_id", [
                "house_subscriber_id" => $subscriberId,
                "msg_id" => $msgId,
            ]);
        } else {
            return $this->db->modify("update inbox set delivered = 1 where delivered = 0 and house_subscriber_id = :house_subscriber_id", [
                "house_subscriber_id" => $subscriberId,
            ]);
        }
    }

    public function unreaded(int $subscriberId): array|bool
    {
        return $this->db->get("select count(*) as unreaded from inbox where house_subscriber_id = :house_subscriber_id and readed = 0", [
            "house_subscriber_id" => $subscriberId,
        ],
            [
                "unreaded" => "unreaded",
            ],
            [
                "fieldlify"
            ]);
    }

    public function undelivered(int $subscriberId): array|bool
    {
        return $this->db->get("select count(*) as undelivered from inbox where house_subscriber_id = :house_subscriber_id and delivered = 0", [
            "house_subscriber_id" => $subscriberId,
        ],
            [
                "undelivered" => "undelivered",
            ],
            [
                "fieldlify"
            ]);
    }
}
