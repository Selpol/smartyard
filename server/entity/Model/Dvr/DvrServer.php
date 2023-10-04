<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Dvr;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $id
 *
 * @property string $title
 *
 * @property string $type
 *
 * @property string $url
 *
 * @property string $token
 *
 * @property string $created_at
 * @property string $updated_at
 */
class DvrServer extends Entity
{
    public static string $table = 'dvr_servers';

    public static string $columnIdStrategy = 'dvr_servers_id_seq';

    public static ?string $columnCreate = 'created_at';
    public static ?string $columnUpdate = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => [Rule::id()],

            'title' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'type' => [Rule::required(), Rule::in(['flussonic', 'trassir']), Rule::nonNullable()],

            'url' => [Rule::required(), Rule::url(), Rule::nonNullable()],

            'token' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'created_at' => [Rule::length(32)],
            'updated_at' => [Rule::length(32)]
        ];
    }
}