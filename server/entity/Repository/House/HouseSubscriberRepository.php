<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\House;

use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method HouseSubscriber fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method HouseSubscriber[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<HouseSubscriber> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method HouseSubscriber|null findById(int $id, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, HouseSubscriber>
 */
#[Singleton]
readonly class HouseSubscriberRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(HouseSubscriber::class);
    }
}