<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Model\Task;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method Task fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method Task[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<Task> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method Task|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, Task>
 */
#[Singleton]
readonly class TaskRepository extends EntityRepository
{
    public function __construct()
    {
        parent::__construct(Task::class);
    }
}