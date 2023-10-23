<?php declare(strict_types=1);

namespace Selpol\Entity\Model;

use Selpol\Framework\Entity\Entity;

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

    public static ?string $columnCreatedAt = 'created_at';
    public static ?string $columnUpdateAt = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'data' => rule()->required()->nonNullable(),

            'title' => rule()->required()->string()->nonNullable(),
            'message' => rule()->required()->string()->clamp(0, 4096)->nonNullable(),

            'status' => rule()->required()->int()->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}