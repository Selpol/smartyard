<?php declare(strict_types=1);

namespace Selpol\Entity\Model;

use Selpol\Entity\Repository\ScheduleRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property string $title
 *
 * @property string $time
 * @property string $script
 * 
 * @property ?string $task
 *
 * @property int<0, 1> $status
 *
 * @property string $created_at
 * @property string $updated_at
 */
class Schedule extends Entity
{
    /**
     * @use RepositoryTrait<ScheduleRepository>
     */
    use RepositoryTrait;

    public static string $table = 'schedule';

    public static ?string $columnCreatedAt = 'created_at';

    public static ?string $columnUpdateAt = 'updated_at';

    public static ?array $fillable = [
        'title' => true,

        'time' => true,
        'script' => true,

        'task' => true,

        'status' => true,
    ];

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'title' => rule()->string()->max(256)->exist(),

            'time' => rule()->string()->exist(),
            'script' => rule()->string()->exist(),

            'task' => rule()->string(),

            'status' => rule()->int()->exist(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}