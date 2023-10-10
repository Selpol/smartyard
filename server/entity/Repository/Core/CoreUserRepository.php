<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Core;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Entity\Repository;
use Selpol\Service\Database\Page;

/**
 * @method CoreUser fetchRaw(string $query, array $params = [])
 * @method CoreUser[] fetchAllRaw(string $query, array $params = [])
 * @method Page<CoreUser> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method CoreUser findById(mixed $id)
 *
 * @extends Repository<int, CoreUser>
 */
class CoreUserRepository extends Repository
{
    protected bool $audit = true;

    protected function __construct()
    {
        parent::__construct(CoreUser::class);
    }
}