<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Device;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Repository;

/**
 * @method DeviceIntercom fetch(string $query, array $params = [])
 * @method DeviceIntercom[] fetchAll(string $query, array $params = [])
 * @method DeviceIntercom[] fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method DeviceIntercom findById(mixed $id)
 *
 * @extends Repository<int, DeviceIntercom>
 */
class DeviceIntercomRepository extends Repository
{
    protected bool $audit = true;

    protected function __construct()
    {
        parent::__construct(DeviceIntercom::class);
    }
}