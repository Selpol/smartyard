<?php declare(strict_types=1);

namespace Selpol\Entity\Model;

use Selpol\Entity\Repository\TaskRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property string $data
 *
 * @property string|null $class
 *
 * @property string $title
 * @property string $message
 *
 * @property int<0, 1> $status
 *
 * @property string $created_at
 * @property string $updated_at
 */
class Task extends Entity
{
    /**
     * @use RepositoryTrait<TaskRepository>
     */
    use RepositoryTrait;

    public static string $table = 'task';

    public static string $columnIdStrategy = 'task_id_seq';

    public static ?string $columnCreatedAt = 'created_at';

    public static ?string $columnUpdateAt = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'data' => rule()->required()->nonNullable(),

            'class' => rule()->string(),

            'title' => rule()->required()->string()->nonNullable(),
            'message' => rule()->required()->string()->clamp(0, 4096)->nonNullable(),

            'status' => rule()->required()->int()->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}