<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Device;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Repository;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Database\Page;

/**
 * @method DeviceIntercom fetchRaw(string $query, array $params = [])
 * @method DeviceIntercom[] fetchAllRaw(string $query, array $params = [])
 * @method Page<DeviceIntercom> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method DeviceIntercom findById(mixed $id)
 *
 * @extends Repository<int, DeviceIntercom>
 */
#[Singleton]
class DeviceIntercomRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(DeviceIntercom::class);
    }
}