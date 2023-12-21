<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\Inbox;

use Selpol\Entity\Model\Inbox\InboxMessage;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method InboxMessage|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method InboxMessage[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<InboxMessage> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method InboxMessage|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, InboxMessage>
 */
#[Singleton]
readonly class InboxMessageRepository extends EntityRepository
{
    /**
     * @use AuditTrait<InboxMessage>
     */
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(InboxMessage::class);

        $this->auditName = 'Сообщение';
    }
}