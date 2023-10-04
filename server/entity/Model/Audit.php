<?php declare(strict_types=1);

namespace Selpol\Entity\Model;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

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

    public static ?string $columnCreate = 'created_at';
    public static ?string $columnUpdate = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => [Rule::id()],

            'user_id' => [Rule::id()],

            'auditable_id' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'auditable_type' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'event_ip' => [Rule::required(), Rule::ipV4(), Rule::nonNullable()],
            'event_type' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'event_target' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'event_code' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'event_message' => [Rule::required(), Rule::length(max: 4096), Rule::nonNullable()],

            'created_at' => [Rule::length(max: 32)],
            'updated_at' => [Rule::length(max: 32)]
        ];
    }
}