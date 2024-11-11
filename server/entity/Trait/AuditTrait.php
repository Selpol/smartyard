<?php declare(strict_types=1);

namespace Selpol\Entity\Trait;

use Selpol\Feature\Audit\AuditFeature;
use Selpol\Framework\Entity\Entity;

/**
 * @template T of Entity
 */
trait AuditTrait
{
    protected readonly string $auditName;

    public function insert(Entity $entity): void
    {
        parent::insert($entity);

        if ($this->canAudit()) {
            $this->audit($entity, 'insert', $this->getAuditMessage($entity, 0));
        }
    }

    public function update(Entity $entity): void
    {
        parent::update($entity);

        if ($this->canAudit()) {
            $this->audit($entity, 'update', $this->getAuditMessage($entity, 1));
        }
    }

    public function delete(Entity $entity): void
    {
        parent::delete($entity);

        if ($this->canAudit()) {
            $this->audit($entity, 'update', $this->getAuditMessage($entity, 2));
        }
    }

    private function canAudit(): bool
    {
        return container(AuditFeature::class)->canAudit();
    }

    private function audit(Entity $entity, string $eventType, string $eventMessage): void
    {
        container(AuditFeature::class)->audit(strval($entity->{$this->meta->columnId}), $this->meta->class, $eventType, $eventMessage);
    }

    protected function getAuditName(): string
    {
        return '[' . ($this->auditName ?? $this->meta->table) . ']';
    }

    protected function getAuditMessage(Entity $entity, int $type): string
    {
        return match ($type) {
            0 => $this->getAuditName() . ' Добавление новой сущности',
            1 => $this->getAuditName() . ' Обновление сущности',
            2 => $this->getAuditName() . ' Удаление сущности',

            default => 'Неизвестный тип операции'
        };
    }
}