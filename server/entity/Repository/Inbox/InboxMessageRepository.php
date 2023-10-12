<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Inbox;

use Selpol\Entity\Criteria;
use Selpol\Entity\Model\Inbox\InboxMessage;
use Selpol\Entity\Repository;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Database\Page;

/**
 * @method InboxMessage fetchRaw(string $query, array $params = [])
 * @method InboxMessage[] fetchAllRaw(string $query, array $params = [])
 * @method Page<InboxMessage> fetchPaginate(int $page, int $size, ?Criteria $criteria = null)
 *
 * @method InboxMessage findById(int $id)
 *
 * @extends Repository<int, InboxMessage>
 */
#[Singleton]
class InboxMessageRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(InboxMessage::class);
    }
}