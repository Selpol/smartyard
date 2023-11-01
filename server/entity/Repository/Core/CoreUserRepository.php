<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Core;

use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method CoreUser fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method CoreUser[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<CoreUser> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method CoreUser|null findById(mixed $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, CoreUser>
 */
#[Singleton]
readonly class CoreUserRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(CoreUser::class);
    }
}