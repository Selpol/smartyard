<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Address;

use Selpol\Entity\Model\Address\AddressSettlement;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method AddressSettlement fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method AddressSettlement[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<AddressSettlement> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method AddressSettlement|null findById(int $id, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, AddressSettlement>
 */
#[Singleton]
readonly class AddressSettlementRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(AddressSettlement::class);
    }
}