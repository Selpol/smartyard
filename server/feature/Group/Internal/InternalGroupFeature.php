<?php declare(strict_types=1);

namespace Selpol\Feature\Group\Internal;

use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;
use Selpol\Feature\Group\GroupFeature;
use Selpol\Service\MongoService;

readonly class InternalGroupFeature extends GroupFeature
{
    private string $database;

    public function __construct()
    {
        $this->database = config_get('feature.group.database', self::DEFAULT_DATABASE);
    }

    /**
     * @param string|null $name
     * @param string|null $type
     * @param string|null $for
     * @param mixed $id
     * @param int|null $page
     * @param int|null $limit
     * @return array[]
     */
    public function find(?string $name, ?string $type, ?string $for, mixed $id, ?int $page, ?int $limit): array
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
            if ($document instanceof BSONDocument)
                $result[] = $document->getArrayCopy();
            else if ($document)
                $result[] = $document;
        }

        return $result;
    }

    /**
     * @param string $for
     * @param int $id
     * @param int $address
     * @return array[]
     */
    public function findByForAndAddress(string $for, mixed $id, int $address): array
    {
        $cursor = $this->getCollection()->find(['type' => self::TYPE_ADDRESS, 'for' => $for, 'id' => $id, 'value' => ['$in' => [$id]]]);

        $result = [];

        foreach ($cursor as $document) {
            if ($document instanceof BSONDocument)
                $result[] = $document->getArrayCopy();
            else if ($document)
                $result[] = $document;
        }

        return $result;
    }

    public function insert(string $name, string $type, string $for, mixed $id, array $value): string|bool
    {
        $result = $this->getCollection()->insertOne(['name' => $name, 'type' => $type, 'for' => $for, 'id' => $id, 'value' => $value]);

        if ($result->getInsertedCount() === 1) {
            $insertedId = $result->getInsertedId();

            if ($insertedId instanceof ObjectId)
                return $insertedId->jsonSerialize()['_id'];

            return $insertedId;
        } else return false;
    }

    public function get(string $oid): array|bool
    {
        $result = $this->getCollection()->findOne(['_id' => new ObjectId($oid)]);

        if ($result) {
            if ($result instanceof BSONDocument)
                $result = $result->getArrayCopy();

            if (is_array($result))
                return $result;
        }

        return false;
    }

    public function update(string $oid, string $name, string $type, string $for, mixed $id, array $value): bool
    {
        $result = $this->getCollection()->updateOne(['_id' => new ObjectId($oid)], ['$set' => ['name' => $name, 'type' => $type, 'for' => $for, 'id' => $id, 'value' => $value]]);

        return $result->getModifiedCount() === 1;
    }

    public function delete(string $oid): bool
    {
        $result = $this->getCollection()->deleteOne(['_id' => new ObjectId($oid)]);

        return $result->getDeletedCount() === 1;
    }

    private function getCollection(): Collection
    {
        return container(MongoService::class)->getClient()->{$this->database}->selectCollection('group');
    }
}