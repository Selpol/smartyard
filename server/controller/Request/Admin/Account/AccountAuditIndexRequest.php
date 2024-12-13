<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Account;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read null|int $user_id Идентификатор пользователя
 *
 * @property-read null|string $auditable_id Идентификатор сущности
 * @property-read null|string $auditable_type Тип сущности
 *
 * @property-read null|string $event_ip IP-адрес с которого произошел аудит
 * @property-read null|string $event_type Тип аудита
 * @property-read null|string $event_target Адрес аудита
 * @property-read null|string $event_code Код аудита
 * @property-read null|string $event_message Сообщение аудита
 *
 * @property-read int $page Страница
 * @property-read int $size Размер страницы
 */
readonly class AccountAuditIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'user_id' => rule()->int()->clamp(0),

            'auditable_id' => rule()->string()->max(1024),
            'auditable_type' => rule()->string()->max(1024),

            'event_ip' => rule()->ipV4(),
            'event_type' => rule()->string()->max(1024),
            'event_target' => rule()->string()->max(1024),
            'event_code' => rule()->string()->max(1024),
            'event_message' => rule()->string()->max(2048),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ];
    }
}