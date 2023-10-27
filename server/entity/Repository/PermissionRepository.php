<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Model\Permission;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method Permission fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method Permission[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<Permission> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method Permission|null findById(int $id, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, Permission>
 */
#[Singleton]
readonly class PermissionRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(Permission::class);
    }

    public function findByTitle(string $title): Permission
    {
        return $this->fetch(criteria()->equal('title', $title));
    }
}