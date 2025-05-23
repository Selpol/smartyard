<?php declare(strict_types=1);

namespace Selpol\Entity\Repository\House;

use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Entity\Trait\AuditTrait;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Entity\EntityCriteria;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Framework\Entity\EntityRepository;
use Selpol\Framework\Entity\EntitySetting;

/**
 * @method HouseKey|null fetch(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method HouseKey[] fetchAll(?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 * @method EntityPage<HouseKey> fetchPage(int $page, int $size, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @method HouseKey|null findById(int $id, ?EntityCriteria $criteria = null, ?EntitySetting $setting = null)
 *
 * @extends EntityRepository<int, HouseKey>
 */
#[Singleton]
readonly class HouseKeyRepository extends EntityRepository
{
    /**
     * @use AuditTrait<HouseKey>
     */
    use AuditTrait;

    public function __construct()
    {
        parent::__construct(HouseKey::class);

        $this->auditName = 'Дом-Ключ';
    }

    protected function getAuditMessage(HouseKey $entity, int $type): string
    {
        return match ($type) {
            0 => $this->getAuditName() . ' Добавление ключа ' . $entity->rfid . ' в ' . $this->getFlatApartment($entity->access_to),
            1 => $this->getAuditName() . ' Обновление ключа ' . $entity->rfid . ' в ' . $this->getFlatApartment($entity->access_to) . ' -' . $entity->comments,
            2 => $this->getAuditName() . ' Удаление ключа ' . $entity->rfid . ' из ' . $this->getFlatApartment($entity->access_to),

            default => 'Неизвестный тип операции'
        };
    }

    private function getFlatApartment(int $value): string
    {
        return 'кв. ' . (HouseFlat::findById($value, setting: setting()->columns(['flat']))?->flat ?? 0) . ' (' . $value . ')';
    }
}