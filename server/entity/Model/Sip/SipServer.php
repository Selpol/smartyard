<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Sip;

use Selpol\Framework\Entity\Entity;

/**
 * @property int $id
 *
 * @property string $title
 * @property string $type
 *
 * @property string $trunk
 *
 * @property string $external_ip
 * @property string $internal_ip
 *
 * @property string $created_at
 * @property string $updated_at
 */
class SipServer extends Entity
{
    public static string $table = 'sip_servers';

    public static string $columnIdStrategy = 'sip_servers_id_seq';

    public static ?string $columnCreateAt = 'created_at';
    public static ?string $columnUpdateAt = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'title' => rule()->required()->string()->nonNullable(),
            'type' => rule()->required()->in(['asterisk'])->nonNullable(),

            'trunk' => rule()->required()->string()->nonNullable(),

            'external_ip' => rule()->required()->ipV4()->nonNullable(),
            'internal_ip' => rule()->required()->ipV4()->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}