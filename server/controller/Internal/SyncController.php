<?php

namespace Selpol\Controller\Internal;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Selpol\Controller\Controller;
use Selpol\Http\Response;
use Selpol\Service\DatabaseService;
use Selpol\Validator\Rule;

class SyncController extends Controller
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function getHouseGroup(): Response
    {
        $body = $this->request->getParsedBody();

        $db = container(DatabaseService::class);

        $houses = $db->get('SELECT address_house_id, house_uuid FROM addresses_houses WHERE house_uuid IN (' . implode(', ', $body) . ')');

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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function addSubscriberGroup(): Response
    {
        $body = $this->request->getParsedBody();

        $result = [];

        foreach ($body as $subscriber) {
            $validate = validator($subscriber, [
                'id' => [Rule::required(), Rule::length(11, 11), Rule::nonNullable()],
                'audJti' => [Rule::required(), Rule::nonNullable()],
                'name' => [Rule::required(), Rule::length(1, 32), Rule::nonNullable()],
                'patronymic' => [Rule::required(), Rule::length(1, 32), Rule::nonNullable()]
            ]);

            $subscriberId = backend('households')->addSubscriber($validate['id'], $validate['name'], $validate['patronymic'], $validate['audJti']);

            if ($subscriberId)
                $result[$subscriberId] = true;
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

        $households = backend('households');

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
            $validate = validator($item, ['id' => [Rule::id()], 'autoBlock' => [Rule::required(), Rule::bool(), Rule::nonNullable()]]);

            if ($db->modify('UPDATE houses_flats SET auto_block = :auto_block WHERE house_flat_id = :flat_id', ['auto_block' => $validate['autoBlock'], 'flat_id' => $validate['id']]))
                $result[$validate['id']] = true;
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
            $validate = validator($item, ['subscriber' => [Rule::id()], 'flat' => [Rule::id()], 'role' => [Rule::required(), Rule::int(), Rule::nonNullable()]]);

            if ($db->insert('INSERT INTO houses_flats_subscribers(house_subscriber_id, house_flat_id, role) VALUES (:subscriber_id, :flat_id, :role)', ['subscriber_id' => $validate['subscriber'], 'flat_id' => $validate['flat'], 'role' => $validate['role']]))
                $result[$validate['subscriber'] . $validate['flat']] = true;
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
            $validate = validator($item, ['subscriber' => [Rule::id()], 'flat' => [Rule::id()]]);

            if ($db->modify('DELETE FROM houses_flats_subscribers WHERE house_subscriber_id = :subscriber_id AND house_flat_id = :flat_id', ['subscriber_id' => $validate['subscriber'], 'flat_id' => $validate['flat']]))
                $result[$validate['subscriber']] = true;
        }

        if (count($result))
            return $this->rbtResponse(data: $result);

        return $this->rbtResponse();
    }
}