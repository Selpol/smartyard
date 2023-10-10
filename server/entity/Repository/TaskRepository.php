<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Task;
use Selpol\Entity\Repository;
use Selpol\Service\Database\Page;

/**
 * @method Task fetchRaw(string $query, array $params = [])
 * @method Task[] fetchAllRaw(string $query, array $params = [])
 * @method Page<Task> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
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