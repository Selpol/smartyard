<?php declare(strict_types=1);

namespace Selpol\Feature\Group\Internal;

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

    public function fetchPage(?string $name, ?string $type, ?string $for, mixed $id, ?int $page, ?int $limit): array|bool
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

        foreach ($cursor as $document)
            $result[] = json_decode(json_encode($document), true);

        return $result;
    }

    public function findByAddress(int $id): array
    {
        $cursor = $this->getCollection()->find(['type' => self::TYPE_ADDRESS, 'value' => ['$in' => [$id]]]);

        $result = [];

        foreach ($cursor as $document)
            $result[] = json_decode(json_encode($document), true);

        return $result;
    }

    public function insert(string $name, string $type, string $for, mixed $id, array $value): bool
    {
        $result = $this->getCollection()->insertOne(['name' => $name, 'type' => $type, 'for' => $for, 'id' => $id, 'value' => $value]);

        return $result->getInsertedCount() === 1;
    }

    public function get(string $name, string $type, string $for, mixed $id): array|bool
    {
        $result = $this->getCollection()->findOne(['name' => $name, 'type' => $type, 'for' => $for, 'id' => $id]);

        if ($result) {
            if ($result instanceof BSONDocument)
                $result = json_decode(json_encode($result), true);

            if (is_array($result))
                return $result;
        }

        return false;
    }

    public function update(string $name, string $type, string $for, mixed $id, array $value): bool
    {
        $result = $this->getCollection()->updateOne(['name' => $name, 'type' => $type, 'for' => $for, 'id' => $id], ['name' => $name, 'type' => $type, 'for' => $for, 'id' => $id, 'value' => $value]);

        return $result->getModifiedCount() === 1;
    }

    public function delete(string $name, string $type, string $for, mixed $id): bool
    {
        $result = $this->getCollection()->deleteOne(['name' => $name, 'type' => $type, 'for' => $for, 'id' => $id]);

        return $result->getDeletedCount() === 1;
    }

    private function getCollection(): Collection
    {
        return container(MongoService::class)->getClient()->{$this->database}->selectCollection('group');
    }
}