<?php

namespace Selpol\Feature\Inbox;

use Selpol\Feature\Feature;

abstract class InboxFeature extends Feature
{
    abstract public function sendMessage(int $subscriberId, string $title, string $msg, string $action = 'inbox'): string|bool;

    abstract public function getMessages(int $subscriberId, string $by, mixed $params): array|bool;

    abstract public function markMessageAsRead(int $subscriberId, int|bool $msgId = false): bool|int;

    abstract public function markMessageAsDelivered(int $subscriberId, int|bool $msgId = false): bool|int;

    abstract public function msgMonths(int $subscriberId): array;

    abstract public function unRead(int $subscriberId): array|bool;

    abstract public function undelivered(int $subscriberId): array|bool;
}