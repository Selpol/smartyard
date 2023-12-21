<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Address;

use Selpol\Entity\Model\Address\AddressStreet;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method AddressStreet|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method AddressStreet[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<AddressStreet> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method AddressStreet|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, AddressStreet>
 */
#[Singleton]
readonly class AddressStreetRepository extends EntityRepository
{
    /**
     * @use AuditTrait<AddressStreet>
     */
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(AddressStreet::class);

        $this->auditName = 'Адрес-Улица';
    }
}