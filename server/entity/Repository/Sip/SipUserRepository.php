<?php

namespace Selpol\Entity\Repository\Sip;

use Selpol\Entity\Model\Sip\SipUser;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method SipUser|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method SipUser[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<SipUser> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method SipUser|null findById(mixed $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, SipUser>
 */
#[Singleton]
readonly class SipUserRepository extends EntityRepository
{
    use AuditTrait;

    public string $auditName;

    public function __construct()
    {
        parent::__construct(SipUser::class);

        $this->auditName = 'Sip-Пользователь';
    }

    public function findByIdAndType(int $id, int $type): ?SipUser
    {
        return $this->fetch(criteria()->equal('id', $id)->equal('type', $type));
    }
}