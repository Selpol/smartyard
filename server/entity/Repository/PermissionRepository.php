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
 * @method Permission|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method Permission[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<Permission> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method Permission|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, Permission>
 */
#[Singleton]
readonly class PermissionRepository extends EntityRepository
{
    use AuditTrait;

    public string $auditName;

    public function __construct()
    {
        parent::__construct(Permission::class);

        $this->auditName = 'Разрешение';
    }

    public function findByTitle(string $title): ?Permission
    {
        return $this->fetch(criteria()->equal('title', $title));
    }

    /**
     * @param int $roleId
     * @return Permission[]
     */
    public function findByRoleId(int $roleId): array
    {
        return $this->fetchAll(criteria()->where('id IN(SELECT permission_id FROM role_permission WHERE role_id = :role_id)')->bind('role_id', $roleId));
    }

    /**
     * @param int $userId
     * @return Permission[]
     */
    public function findByUserId(int $userId): array
    {
        return $this->fetchAll(criteria()->where('id IN(SELECT permission_id FROM user_permission WHERE user_id = :user_id)')->bind('user_id', $userId));
    }
}