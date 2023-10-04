<?php declare(strict_types=1);

namespace Selpol\Entity\Model;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

/**
 * @property int $id
 *
 * @property string $data
 *
 * @property string $title
 * @property string $message
 *
 * @property int $status
 *
 * @property string $created_at
 * @property string $updated_at
 */
class Task extends Entity
{
    public static string $table = 'task';

    public static string $columnIdStrategy = 'task_id_seq';

    public static ?string $columnCreate = 'created_at';
    public static ?string $columnUpdate = 'updated_at';

    protected static function getColumns(): array
    {
        return [
            static::$columnId => [Rule::id()],

            'data' => [Rule::required(), Rule::nonNullable()],

            'title' => [Rule::required(), Rule::length(), Rule::nonNullable()],
            'message' => [Rule::required(), Rule::length(max: 4096), Rule::nonNullable()],

            'status' => [Rule::required(), Rule::int(), Rule::nonNullable()],

            'created_at' => [Rule::length(max: 32)],
            'updated_at' => [Rule::length(max: 32)]
        ];
    }
}