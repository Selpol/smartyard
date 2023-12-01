<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Address;

use Selpol\Entity\Model\Address\AddressRegion;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method AddressRegion|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method AddressRegion[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<AddressRegion> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method AddressRegion|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, AddressRegion>
 */
#[Singleton]
readonly class AddressRegionRepository extends EntityRepository
{
    use AuditTrait;

    public string $auditName;

    public function __construct()
    {
        parent::__construct(AddressRegion::class);

        $this->auditName = 'Адрес-Регион';
    }
}