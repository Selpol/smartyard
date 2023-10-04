<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Frs;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $id
 *
 * @property string $title
 *
 * @property string $url
 *
 * @property string $created_at
 * @property string $updated_at
 */
class FrsServer extends Entity
{
    public static string $table = 'frs_servers';

    public static ?string $columnCreate = 'created_at';
    public static ?string $columnUpdate = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => [Rule::id()],

            'title' => [Rule::required(), Rule::length(), Rule::nonNullable()],

            'url' => [Rule::required(), Rule::url(), Rule::nonNullable()],

            'created_at' => [Rule::length(32)],
            'updated_at' => [Rule::length(32)]
        ];
    }
}