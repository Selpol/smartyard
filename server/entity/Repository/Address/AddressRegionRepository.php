<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Address;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Address\AddressRegion;
use Selpol\Entity\Repository;
use Selpol\Service\Database\Page;

/**
 * @method AddressRegion fetchRaw(string $query, array $params = [])
 * @method AddressRegion[] fetchAllRaw(string $query, array $params = [])
 * @method Page<AddressRegion> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method AddressRegion findById(int $id)
 *
 * @extends Repository<int, AddressRegion>
 */
class AddressRegionRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(AddressRegion::class);
    }
}