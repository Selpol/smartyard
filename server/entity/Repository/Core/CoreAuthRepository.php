<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Core;

use Selpol\Entity\Model\Core\CoreAuth;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method CoreAuth|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method CoreAuth[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<CoreAuth> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method CoreAuth|null findById(mixed $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, CoreAuth>
 */
#[Singleton]
readonly class CoreAuthRepository extends EntityRepository
{
    /**
     * @use AuditTrait<CoreAuth>
     */
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(CoreAuth::class);

        $this->auditName = 'Ядро-Авторизация';
    }
}