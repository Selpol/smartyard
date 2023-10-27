<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Address;

use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method AddressHouse fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method AddressHouse[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<AddressHouse> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method AddressHouse|null findById(int $id, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, AddressHouse>
 */
#[Singleton]
readonly class AddressHouseRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(AddressHouse::class);
    }
}