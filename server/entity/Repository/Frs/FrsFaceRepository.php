<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Frs;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Frs\FrsFace;
use Selpol\Entity\Repository;

/**
 * @method FrsFace fetch(string $query, array $params = [])
 * @method FrsFace[] fetchAll(string $query, array $params = [])
 * @method FrsFace[] fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method FrsFace findById(int $id)
 *
 * @extends Repository<int, FrsFace>
 */
class FrsFaceRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(FrsFace::class);
    }
}