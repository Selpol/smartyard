<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Model\Task;
use Selpol\Entity\Repository;

/**
 * @method Task fetch(string $query, array $params = [])
 * @method Task[] fetchAll(string $query, array $params = [])
 *
 * @method Task findById(int $id)
 *
 * @extends Repository<int, Task>
 */
class TaskRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(Task::class);
    }
}