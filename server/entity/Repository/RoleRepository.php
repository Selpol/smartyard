<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Model\Role;
use Selpol\Entity\Repository;

/**
 * @method Role fetch(string $query, array $params = [])
 * @method Role[] fetchAll(string $query, array $params = [])
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