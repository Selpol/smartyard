<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Model\Role;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method Role fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method Role[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<Role> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method Role|null findById(int $id, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, Role>
 */
#[Singleton]
readonly class RoleRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(Role::class);
    }
}