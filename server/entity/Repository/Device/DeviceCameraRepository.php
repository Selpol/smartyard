<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Device;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Repository;

/**
 * @method DeviceCamera fetch(string $query, array $params = [])
 * @method DeviceCamera[] fetchAll(string $query, array $params = [])
 *
 * @method DeviceCamera findById(mixed $id)
 *
 * @extends Repository<int, DeviceCamera>
 */
class DeviceCameraRepository extends Repository
{
    protected bool $audit = true;

    protected function __construct()
    {
        parent::__construct(DeviceCamera::class);
    }
}