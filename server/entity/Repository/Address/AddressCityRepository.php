<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Address;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Address\AddressCity;
use Selpol\Entity\Repository;
use Selpol\Service\Database\Page;

/**
 * @method AddressCity fetchRaw(string $query, array $params = [])
 * @method AddressCity[] fetchAllRaw(string $query, array $params = [])
 * @method Page<AddressCity> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method AddressCity findById(int $id)
 *
 * @extends Repository<int, AddressCity>
 */
class AddressCityRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(AddressCity::class);
    }
}