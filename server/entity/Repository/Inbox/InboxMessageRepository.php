<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Inbox;

use Selpol\Entity\Model\Inbox\InboxMessage;
use Selpol\Entity\Repository;

/**
 * @method InboxMessage fetch(string $query, array $params = [])
 * @method InboxMessage[] fetchAll(string $query, array $params = [])
 *
 * @method InboxMessage findById(int $id)
 *
 * @extends Repository<int, InboxMessage>
 */
class InboxMessageRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(InboxMessage::class);
    }
}