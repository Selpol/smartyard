<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\House;

use Selpol\Entity\Model\House\HouseGsm;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;

/**
 * @method HouseGsm|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method HouseGsm[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<HouseGsm> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method HouseGsm|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, HouseGsm>
 */
#[Singleton]
readonly class HouseGsmRepository extends EntityRepository
{
    /**
     * @use AuditTrait<HouseGsm>
     */
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(HouseGsm::class);

        $this->auditName = 'Абонент-GSM';
    }
}