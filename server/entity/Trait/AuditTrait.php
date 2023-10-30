<?php

namespace Selpol\Entity\Trait;

use Selpol\Feature\Audit\AuditFeature;
use Selpol\Framework\Entity\Entity;

trait AuditTrait
{
    public function insert(Entity $entity): bool
    {
        $result = parent::insert($entity);

        if (!$this->canAudit())
            return $result;

        if ($result)
            $this->audit($entity, 'insert', 'Добавление новой сущности');

        return $result;
    }

    public function update(Entity $entity): bool
    {
        $result = parent::update($entity);

        if (!$this->canAudit())
            return $result;

        if ($result)
            $this->audit($entity, 'update', 'Обновление сущности');

        return $result;
    }

    public function delete(Entity $entity): bool
    {
        $result = parent::delete($entity);

        if (!$this->canAudit())
            return $result;

        if ($result)
            $this->audit($entity, 'delete', 'Удаление сущности');

        return $result;
    }

    private function canAudit(): bool
    {
        return container(AuditFeature::class)->canAudit();
    }

    private function audit(Entity $entity, string $eventType, string $eventMessage): void
    {
        container(AuditFeature::class)->audit($entity->{$this->meta->columnId}, $this->meta->class, $eventType, $eventMessage);
    }
}