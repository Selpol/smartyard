<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Core;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

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
            static::$columnId => [Rule::id()],

            'var_name' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'var_value' => [Rule::length(max: 4096)]
        ];
    }
}