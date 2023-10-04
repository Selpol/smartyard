<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Model\Permission;
use Selpol\Entity\Repository;

/**
 * @method Permission fetch(string $query, array $params = [])
 * @method Permission[] fetchAll(string $query, array $params = [])
 *
 * @method Permission findById(int $id)
 *
 * @extends Repository<int, Permission>
 */
class PermissionRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(Permission::class);
    }
}