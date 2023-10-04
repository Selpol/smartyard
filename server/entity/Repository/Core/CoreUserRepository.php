<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Core;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Entity\Repository;

/**
 * @method CoreUser fetch(string $query, array $params = [])
 * @method CoreUser[] fetchAll(string $query, array $params = [])
 * @method CoreUser[] fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method CoreUser findById(mixed $id)
 *
 * @extends Repository<int, CoreUser>
 */
class CoreUserRepository extends Repository
{
    protected bool $audit = true;

    protected function __construct()
    {
        parent::__construct(CoreUser::class);
    }
}