<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\House;

use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method HouseFlat fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method HouseFlat[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<HouseFlat> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method HouseFlat|null findById(int $id, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, HouseFlat>
 */
#[Singleton]
readonly class HouseFlatRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(HouseFlat::class);
    }
}