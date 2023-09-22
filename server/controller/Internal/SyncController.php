<?php

namespace Selpol\Controller\Internal;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Feature\House\HouseFeature;
use Selpol\Http\Response;
use Selpol\Service\DatabaseService;
use Selpol\Validator\Rule;
use Throwable;

class SyncController extends Controller
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function getHouseGroup(): Response
    {
        $body = $this->request->getParsedBody();

        $db = container(DatabaseService::class);

        $houses = $db->get('SELECT address_house_id, house_uuid FROM addresses_houses WHERE house_uuid IN (' . implode(', ', array_map(static fn(string $value) => "'" . $value . "'", $body)) . ')');

        $result = [];

        if (count($houses)) {
            for ($i = 0; $i < count($houses); $i++)
                $result[$houses[$i]['house_uuid']] = [
                    'id' => $houses[$i]['address_house_id'],
                    'flats' => $db->get('SELECT house_flat_id as id, flat FROM  houses_flats WHERE address_house_id = :id', ['id' => $houses[$i]['address_house_id']])
                ];

            return $this->rbtResponse(data: $result);
        }

        return $this->rbtResponse(404);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function addSubscriberGroup(): Response
    {
        $body = $this->request->getParsedBody();

        $households = container(HouseFeature::class);

        $result = [];

        foreach ($body as $subscriber) {
            try {
                $validate = validator($subscriber, [
                    'id' => [Rule::required(), Rule::length(11, 11), Rule::nonNullable()],
                    'audJti' => [Rule::required(), Rule::nonNullable()],
                    'name' => [Rule::required(), Rule::length(1, 32), Rule::nonNullable()],
                    'patronymic' => [Rule::required(), Rule::length(1, 32), Rule::nonNullable()]
                ]);

                $subscriberId = $households->addSubscriber($validate['id'], $validate['name'], $validate['patronymic'], $validate['audJti']);

                if ($subscriberId)
                    $result[$validate['id']] = $subscriberId;
            } catch (Throwable $throwable) {
                logger('internal-sync')->error($throwable);
            }
        }

        if (count($result))
            return $this->rbtResponse(data: $result);

        return $this->rbtResponse(404);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function updateSubscriberGroup(): Response
    {
        $body = $this->request->getParsedBody();

        $households = container(HouseFeature::class);

        $result = [];

        foreach ($body as $subscriber) {
            try {
                $validate = validator($subscriber, [
                    'subscriberId' => [Rule::id()],
                    'name' => [Rule::required(), Rule::length(1, 32), Rule::nonNullable()],
                    'patronymic' => [Rule::required(), Rule::length(1, 32), Rule::nonNullable()]
                ]);

                if ($households->modifySubscriber($validate['subscriberId'], ['subscriberName' => $validate['name'], 'subscriberPatronymic' => $validate['patronymic']]))
                    $result[$validate['subscriberId']] = true;
            } catch (Throwable $throwable) {
                logger('internal-sync')->error($throwable);
            }
        }

        if (count($result))
            return $this->rbtResponse(data: $result);

        return $this->rbtResponse(404);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function deleteSubscriberGroup(): Response
    {
        $body = $this->request->getParsedBody();

        $households = container(HouseFeature::class);

        $result = [];

        foreach ($body as $item)
            if ($households->deleteSubscriber($item))
                $result[$item] = true;

        if (count($result))
            return $this->rbtResponse(data: $result);

        return $this->rbtResponse(404);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function updateFlatGroup(): Response
    {
        $body = $this->request->getParsedBody();

        $db = container(DatabaseService::class);

        $result = [];

        foreach ($body as $item) {
            try {
                $validate = validator($item, ['id' => [Rule::id()], 'autoBlock' => [Rule::required(), Rule::bool(), Rule::nonNullable()]]);

                if ($db->modify('UPDATE houses_flats SET auto_block = :auto_block WHERE house_flat_id = :flat_id', ['auto_block' => $validate['autoBlock'], 'flat_id' => $validate['id']]))
                    $result[$validate['id']] = true;
            } catch (Throwable $throwable) {
                logger('internal-sync')->error($throwable);
            }
        }

        if (count($result))
            return $this->rbtResponse(data: $result);

        return $this->rbtResponse(404);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function addSubscriberToFlatGroup(): Response
    {
        $body = $this->request->getParsedBody();

        $db = container(DatabaseService::class);

        $result = [];

        foreach ($body as $item) {
            try {
                $validate = validator($item, ['subscriber' => [Rule::id()], 'flat' => [Rule::id()], 'role' => [Rule::required(), Rule::int(), Rule::nonNullable()]]);

                if ($db->insert('INSERT INTO houses_flats_subscribers(house_subscriber_id, house_flat_id, role) VALUES (:subscriber_id, :flat_id, :role)', ['subscriber_id' => $validate['subscriber'], 'flat_id' => $validate['flat'], 'role' => $validate['role']]))
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
            } catch (Throwable $throwable) {
                logger('internal-sync')->error($throwable);
            }
        }

        if (count($result))
            return $this->rbtResponse(data: $result);

        return $this->rbtResponse(404);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function updateSubscriberToFlatGroup(): Response
    {
        $body = $this->request->getParsedBody();

        $db = container(DatabaseService::class);

        $result = [];

        foreach ($body as $item) {
            try {
                $validate = validator($item, ['subscriber' => [Rule::id()], 'flat' => [Rule::id()], 'role' => [Rule::required(), Rule::int(), Rule::nonNullable()]]);

                if ($db->modify('UPDATE houses_flats_subscribers SET role = :role WHERE house_subscriber_id = :subscriber_id AND house_flat_id = :flat_id', ['subscriber_id' => $validate['subscriber'], 'flat_id' => $validate['flat'], 'role' => $validate['role']]))
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
            } catch (Throwable $throwable) {
                logger('internal-sync')->error($throwable);
            }
        }

        if (count($result))
            return $this->rbtResponse(data: $result);

        return $this->rbtResponse(404);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function deleteSubscriberFromFlatGroup(): Response
    {
        $body = $this->request->getParsedBody();

        $db = container(DatabaseService::class);

        $result = [];

        foreach ($body as $item) {
            try {
                $validate = validator($item, ['subscriber' => [Rule::id()], 'flat' => [Rule::id()]]);

                if ($db->modify('DELETE FROM houses_flats_subscribers WHERE house_subscriber_id = :subscriber_id AND house_flat_id = :flat_id', ['subscriber_id' => $validate['subscriber'], 'flat_id' => $validate['flat']]))
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
            } catch (Throwable $throwable) {
                logger('internal-sync')->error($throwable);
            }
        }

        if (count($result))
            return $this->rbtResponse(data: $result);

        return $this->rbtResponse();
    }
}