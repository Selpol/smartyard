<?php declare(strict_types=1);

namespace Selpol\Entity\Trait;

use Selpol\Feature\Audit\AuditFeature;
use Selpol\Framework\Entity\Entity;
use Throwable;

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
            $this->audit($entity, 'insert', $this->getAuditMessageInsert($entity));
        }
    }

    public function update(Entity $entity): void
    {
        parent::update($entity);

        if ($this->canAudit()) {
            $this->audit($entity, 'update', $this->getAuditMessageUpdate($entity));
        }
    }

    public function delete(Entity $entity): void
    {
        parent::delete($entity);

        if ($this->canAudit()) {
            $this->audit($entity, 'update', $this->getAuditMessageDelete($entity));
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
        return '[' . ($this->auditName ?? 'Сущность') . ']';
    }

    /**
     * @psalm-param T $entity
     * @psalm-return string
     */
    protected function getAuditMessageInsert(Entity $entity): string
    {
        return $this->getAuditName() . ' Добавление новой сущности';
    }

    /**
     * @psalm-param T $entity
     * @psalm-return string
     */
    protected function getAuditMessageUpdate(Entity $entity): string
    {
        return $this->getAuditName() . ' Обновление сущности';
    }

    /**
     * @psalm-param T $entity
     * @psalm-return string
     */
    protected function getAuditMessageDelete(Entity $entity): string
    {
        return $this->getAuditName() . ' Удаление сущности';
    }
}