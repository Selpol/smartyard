<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Device;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method DeviceCamera fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method DeviceCamera[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<DeviceCamera> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method DeviceCamera|null findById(mixed $id, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, DeviceCamera>
 */
#[Singleton]
readonly class DeviceCameraRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(DeviceCamera::class);
    }
}