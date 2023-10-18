<?php

namespace Selpol\Entity\Repository\Sip;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Sip\SipUser;
use Selpol\Entity\Repository;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Database\Page;

/**
 * @method SipUser fetchRaw(string $query, array $params = [])
 * @method SipUser[] fetchAllRaw(string $query, array $params = [])
 * @method Page<SipUser> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method SipUser findById(mixed $id)
 *
 * @extends Repository<int, SipUser>
 */
#[Singleton]
class SipUserRepository extends Repository
{
    protected bool $audit = true;

    public function __construct()
    {
        parent::__construct(SipUser::class);
    }

    public function findByIdAndType(int $id, int $type): SipUser
    {
        return $this->fetchRaw('SELECT * FROM ' . $this->table . ' WHERE id = :id AND type = :type', ['id' => $id, 'type' => $type]);
    }
}