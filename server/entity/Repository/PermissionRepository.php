<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Permission;
use Selpol\Entity\Repository;
use Selpol\Service\Database\Page;

/**
 * @method Permission fetch(string $query, array $params = [])
 * @method Permission[] fetchAll(string $query, array $params = [])
 * @method Page<Permission> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method Permission findById(int $id)
 *
 * @extends Repository<int, Permission>
 */
class PermissionRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(Permission::class);
    }
}