<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Role;
use Selpol\Entity\Repository;
use Selpol\Service\Database\Page;

/**
 * @method Role fetchRaw(string $query, array $params = [])
 * @method Role[] fetchAllRaw(string $query, array $params = [])
 * @method Page<Role> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method Role findById(int $id)
 *
 * @extends Repository<int, Role>
 */
class RoleRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(Role::class);
    }
}