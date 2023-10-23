<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Device;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method DeviceIntercom fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method DeviceIntercom[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<DeviceIntercom> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method DeviceIntercom findById(mixed $id)
 *
 * @extends EntityRepository<int, DeviceIntercom>
 */
#[Singleton]
readonly class DeviceIntercomRepository extends EntityRepository
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(DeviceIntercom::class);
    }
}