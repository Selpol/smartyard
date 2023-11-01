<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Address;

use Selpol\Entity\Model\Address\AddressArea;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method AddressArea fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method AddressArea[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<AddressArea> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method AddressArea|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, AddressArea>
 */
#[Singleton]
readonly class AddressAreaRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(AddressArea::class);
    }
}