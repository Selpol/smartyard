<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Device;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Repository;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Database\Page;

/**
 * @method DeviceCamera fetchRaw(string $query, array $params = [])
 * @method DeviceCamera[] fetchAllRaw(string $query, array $params = [])
 * @method Page<DeviceCamera> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method DeviceCamera findById(mixed $id)
 *
 * @extends Repository<int, DeviceCamera>
 */
#[Singleton]
class DeviceCameraRepository extends Repository
{
    protected bool $audit = true;

    protected function __construct()
    {
        parent::__construct(DeviceCamera::class);
    }
}