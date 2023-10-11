<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Address;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Address\AddressSettlement;
use Selpol\Entity\Repository;
use Selpol\Service\Database\Page;

/**
 * @method AddressSettlement fetchRaw(string $query, array $params = [])
 * @method AddressSettlement[] fetchAllRaw(string $query, array $params = [])
 * @method Page<AddressSettlement> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method AddressSettlement findById(int $id)
 *
 * @extends Repository<int, AddressSettlement>
 */
class AddressSettlementRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(AddressSettlement::class);
    }
}