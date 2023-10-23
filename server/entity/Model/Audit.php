<?php declare(strict_types=1);

namespace Selpol\Entity\Model;

use Selpol\Framework\Entity\Entity;

/**
 * @property int $id
 * @property int $user_id
 *
 * @property string $auditable_id
 * @property string $auditable_type
 *
 * @property string $event_ip
 * @property string $event_type
 * @property string $event_target
 * @property string $event_code
 * @property string $event_message
 *
 * @property string $created_at
 * @property string $updated_at
 */
class Audit extends Entity
{
    public static string $table = 'audit';

    public static string $columnIdStrategy = 'audit_id_seq';

    public static ?string $columnCreateAt = 'created_at';
    public static ?string $columnUpdateAt = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'user_id' => rule()->id(),

            'auditable_id' => rule()->required()->string()->nonNullable(),
            'auditable_type' => rule()->required()->string()->nonNullable(),

            'event_ip' => rule()->required()->ipV4()->nonNullable(),
            'event_type' => rule()->required()->string()->nonNullable(),
            'event_target' => rule()->required()->string()->nonNullable(),
            'event_code' => rule()->required()->string()->nonNullable(),
            'event_message' => rule()->required()->string()->max(4096)->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}