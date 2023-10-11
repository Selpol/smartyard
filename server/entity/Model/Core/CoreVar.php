<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Core;

use Selpol\Entity\Entity;

/**
 * @property int $var_id
 *
 * @property string $var_name
 * @property static|null $var_value
 */
class CoreVar extends Entity
{
    public static string $table = 'core_vars';

    public static string $columnId = 'var_id';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'var_name' => rule()->required()->string()->nonNullable(),
            'var_value' => rule()->string()->max(4096)
        ];
    }
}