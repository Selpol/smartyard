<?php declare(strict_types=1);

namespace Selpol\Entity\Repository;

use Selpol\Entity\Model\Audit;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method Audit fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method Audit[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<Audit> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method Audit findById(int $id)
 *
 * @extends EntityRepository<int, Audit>
 */
#[Singleton]
readonly class AuditRepository extends EntityRepository
{
    public function __construct()
    {
        parent::__construct(Audit::class);
    }
}