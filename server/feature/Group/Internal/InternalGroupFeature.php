<?php declare(strict_types=1);

namespace Selpol\Feature\Group\Internal;

use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Feature\Group\Group;
use Selpol\Feature\Group\GroupFeature;
use Selpol\Service\MongoService;

readonly class InternalGroupFeature extends GroupFeature
{
    private string $database;

    public function __construct()
    {
        $this->database = config_get('feature.group.database', self::DEFAULT_DATABASE);
    }

    public function find(?string $name = null, ?string $type = null, ?string $for = null, mixed $id = null, ?int $page = null, ?int $limit = null): array
    {
        $filter = [];

        if ($name !== null) $filter['name'] = $name;
        if ($type !== null) $filter['type'] = $type;
        if ($for !== null) $filter['for'] = $for;
        if ($id !== null) $filter['id'] = $id;

        $options = [];

        if ($page != null && $limit != null) {
            $options['skip'] = $page * $limit;
            $options['limit'] = $limit;
        }

        $cursor = $this->getCollection()->find($filter, $options);
        $result = [];

        foreach ($cursor as $document) {
            if ($document instanceof BSONDocument) $result[] = new Group($document->getArrayCopy());
            else if ($document) $result[] = new Group($document);
        }

        return $result;
    }

    public function findIn(string $type, string $for, mixed $id, mixed $value): array
    {
        $cursor = $this->getCollection()->find(['type' => $type, 'for' => $for, 'id' => $id, 'value' => ['$in' => [$value]]]);

        $result = [];

        foreach ($cursor as $document) {
            if ($document instanceof BSONDocument) $result[] = new Group($document->getArrayCopy());
            else if ($document) $result[] = new Group($document);
        }

        return $result;
    }

    public function insert(string $name, string $type, string $for, mixed $id, array $value): string|bool
    {
        $result = $this->getCollection()->insertOne(['name' => $name, 'type' => $type, 'for' => $for, 'id' => $id, 'value' => $value]);

        if ($result->getInsertedCount() === 1) {
            $insertedId = $result->getInsertedId();

            if ($insertedId instanceof ObjectId)
                $id = $insertedId->__toString();
            else $id = $insertedId;

            if (container(AuditFeature::class)->canAudit())
                container(AuditFeature::class)->audit($id, Group::class, 'insert', 'Создание группы');

            return $id;
        } else return false;
    }

    public function get(string $oid): Group|bool
    {
        $result = $this->getCollection()->findOne(['_id' => new ObjectId($oid)]);

        if ($result) {
            if ($result instanceof BSONDocument)
                $result = $result->getArrayCopy();

            if (is_array($result))
                return new Group($result);
        }

        return false;
    }

    public function update(string $oid, string $name, string $type, string $for, mixed $id, array $value): bool
    {
        $result = $this->getCollection()->updateOne(['_id' => new ObjectId($oid)], ['$set' => ['name' => $name, 'type' => $type, 'for' => $for, 'id' => $id, 'value' => $value]]);
        $status = $result->getModifiedCount() === 1;

        if ($status) {
            if (container(AuditFeature::class)->canAudit())
                container(AuditFeature::class)->audit($oid, Group::class, 'update', 'Обновление группы');

            return true;
        }

        return false;
    }

    public function delete(string $oid): bool
    {
        $result = $this->getCollection()->deleteOne(['_id' => new ObjectId($oid)]);
        $status = $result->getDeletedCount() == 1;

        if ($status) {
            if (container(AuditFeature::class)->canAudit())
                container(AuditFeature::class)->audit($oid, Group::class, 'delete', 'Удаление группы');

            return true;
        }

        return false;
    }

    public function deleteFor(string $for, mixed $id): bool
    {
        $result = $this->getCollection()->deleteMany(['for' => $for, 'id' => $id]);
        $status = $result->getDeletedCount() > 0;

        if ($status) {
            if (container(AuditFeature::class)->canAudit())
                container(AuditFeature::class)->audit(strval($id), Group::class, 'delete', 'Удаление группы для типа ' . $for);

            return true;
        }

        return false;
    }

    private function getCollection(): Collection
    {
        return container(MongoService::class)->getDatabase($this->database)->selectCollection('group');
    }
}