<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Device;

use Selpol\Entity\Model\Device\DeviceRelay;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method DeviceRelay|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method DeviceRelay[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<DeviceRelay> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method DeviceRelay|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, DeviceRelay>
 */
#[Singleton]
readonly class DeviceRelayRepository extends EntityRepository
{
    /**
     * @use AuditTrait<DeviceRelay>
     */
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(DeviceRelay::class);

        $this->auditName = 'Устройство-Реле';
    }
}