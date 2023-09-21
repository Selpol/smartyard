<?php

namespace backends\inbox;

use backends\backend;

abstract class inbox extends backend
{
    abstract public function sendMessage(int $subscriberId, $title, $msg, $action = "inbox"): string|bool;

    abstract public function getMessages(int $subscriberId, $by, $params): array|bool;

    abstract public function markMessageAsReaded(int $subscriberId, int|bool $msgId = false): bool|int;

    abstract public function markMessageAsDelivered(int $subscriberId, int|bool $msgId = false): bool|int;

    abstract public function msgMonths(int $subscriberId): array;

    abstract public function unreaded(int $subscriberId): array|bool;

    abstract public function undelivered(int $subscriberId): array|bool;
}
