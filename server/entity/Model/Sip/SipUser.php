<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Sip;

use Selpol\Entity\Entity;

/**
 * @property int $id
 *
 * @property int $type
 *
 * @property string $title
 *
 * @property string $password
 *
 * @property string $created_at
 * @property string $updated_at
 */
class SipUser extends Entity
{
    public static string $table = 'sip_user';

    public static string $columnIdStrategy = 'sip_user_id_seq';

    public static ?string $columnCreate = 'created_at';
    public static ?string $columnUpdate = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'type' => rule()->required()->int()->clamp(1, 9)->nonNullable(),

            'title' => rule()->required()->string()->nonNullable(),

            'password' => rule()->required()->string()->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}