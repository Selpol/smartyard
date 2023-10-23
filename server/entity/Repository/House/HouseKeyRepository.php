<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\House;

use Selpol\Entity\Model\House\HouseKey;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method HouseKey fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method HouseKey[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<HouseKey> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method HouseKey findById(int $id)
 *
 * @extends EntityRepository<int, HouseKey>
 */
#[Singleton]
readonly class HouseKeyRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(HouseKey::class);
    }
}