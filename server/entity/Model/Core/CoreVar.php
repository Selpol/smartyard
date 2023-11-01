<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Core;

use Selpol\Entity\Repository\Core\CoreVarRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $var_id
 *
 * @property string $var_name
 * @property string|null $var_value
 *
 * @property string|null $title
 *
 * @property bool $hidden
 * @property bool $editable
 *
 * @property string $created_at
 * @property string $updated_at
 */
class CoreVar extends Entity
{
    /**
     * @use RepositoryTrait<CoreVarRepository>
     */
    use RepositoryTrait;

    public static string $table = 'core_vars';

    public static string $columnId = 'var_id';

    public static ?string $columnCreateAt = 'created_at';
    public static ?string $columnUpdateAt = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'var_name' => rule()->required()->string()->nonNullable(),
            'var_value' => rule()->string()->max(4096),

            'title' => rule()->string(),

            'hidden' => rule()->required()->bool()->nonNullable(),
            'editable' => rule()->required()->bool()->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}