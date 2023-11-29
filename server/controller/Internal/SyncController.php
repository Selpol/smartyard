<?php

namespace Selpol\Controller\Internal;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selpol\Controller\RbtController;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Service\DatabaseService;
use Selpol\Service\Exception\DatabaseException;
use Selpol\Task\Tasks\Inbox\InboxFlatTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomCmsFlatTask;
use Throwable;

#[Controller('/internal/sync')]
readonly class SyncController extends RbtController
{
    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/house')]
    public function getHouseGroup(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        $db = container(DatabaseService::class);

        $houses = $db->get('SELECT address_house_id, house_uuid FROM addresses_houses WHERE house_uuid IN (' . implode(', ', array_map(static fn(string $value) => "'" . $value . "'", $body)) . ')');

        $result = [];

        if (count($houses)) {
            for ($i = 0; $i < count($houses); $i++)
                $result[$houses[$i]['house_uuid']] = [
                    'id' => $houses[$i]['address_house_id'],
                    'flats' => $db->get('SELECT house_flat_id as id, flat FROM  houses_flats WHERE address_house_id = :id', ['id' => $houses[$i]['address_house_id']])
                ];

            return user_response(data: $result);
        }

        return user_response(data: []);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/subscriber')]
    public function addSubscriberGroup(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        $households = container(HouseFeature::class);

        $result = [];

        foreach ($body as $subscriber) {
            try {
                $validate = validator($subscriber, [
                    'id' => rule()->required()->clamp(11, 11)->nonNullable(),
                    'audJti' => rule()->required()->nonNullable(),
                    'name' => rule()->required()->clamp(1, 32)->nonNullable(),
                    'patronymic' => rule()->required()->clamp(1, 32)->nonNullable(),
                ]);
            } catch (Throwable) {
                continue;
            }

            try {
                $subscriberId = $households->addSubscriber($validate['id'], $validate['name'], $validate['patronymic'], $validate['audJti']);

                if ($subscriberId)
                    $result[$validate['id']] = $subscriberId;
            } catch (Throwable $throwable) {
                if ($throwable instanceof DatabaseException && $throwable->isUniqueViolation()) {
                    $subscriber = HouseSubscriber::findById($validate['id'], setting: setting()->nonNullable());

                    $result[$validate['id']] = $subscriber->house_subscriber_id;
                }
            }
        }

        return user_response(data: $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Put('/subscriber')]
    public function updateSubscriberGroup(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        $households = container(HouseFeature::class);

        $result = [];

        foreach ($body as $subscriber) {
            try {
                $validate = validator($subscriber, [
                    'subscriberId' => rule()->id(),
                    'name' => rule()->required()->clamp(1, 32)->nonNullable(),
                    'patronymic' => rule()->required()->clamp(1, 32)->nonNullable(),
                ]);

                if ($households->modifySubscriber($validate['subscriberId'], ['subscriberName' => $validate['name'], 'subscriberPatronymic' => $validate['patronymic']]))
                    $result[$validate['subscriberId']] = true;
            } catch (Throwable) {
            }
        }

        return user_response(data: $result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Delete('/subscriber')]
    public function deleteSubscriberGroup(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        $result = [];

        foreach ($body as $item)
            try {
                if (HouseSubscriber::findById($item, setting: setting()->nonNullable())->delete())
                    $result[$item] = true;
            } catch (Throwable) {
            }

        return user_response(data: $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Put('/flat')]
    public function updateFlatGroup(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        $db = container(DatabaseService::class);

        $result = [];

        foreach ($body as $item) {
            try {
                $validate = validator($item, ['id' => rule()->id(), 'autoBlock' => rule()->required()->bool()->nonNullable()]);

                if ($db->modify('UPDATE houses_flats SET auto_block = :auto_block WHERE house_flat_id = :flat_id', ['auto_block' => $validate['autoBlock'], 'flat_id' => $validate['id']])) {
                    $result[$validate['id']] = true;

                    task(new IntercomCmsFlatTask($validate['id'], boolval($validate['autoBlock'])))->low()->dispatch();
                    task(new InboxFlatTask($validate['id'], 'Обновление статуса квартиры', $validate['autoBlock'] ? 'Ваша квартиры была заблокирована' : 'Ваша квартиры была разблокирована', 'inbox'))->low()->dispatch();
                }
            } catch (Throwable) {
            }
        }

        return user_response(data: $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Post('/link')]
    public function addSubscriberToFlatGroup(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        $db = container(DatabaseService::class);

        $result = [];

        foreach ($body as $item) {
            try {
                $validate = validator($item, ['subscriber' => rule()->id(), 'flat' => rule()->id(), 'role' => rule()->required()->int()->nonNullable()]);
            } catch (Throwable) {
                continue;
            }

            try {
                if ($db->insert('INSERT INTO houses_flats_subscribers(house_subscriber_id, house_flat_id, role) VALUES (:subscriber_id, :flat_id, :role)', ['subscriber_id' => $validate['subscriber'], 'flat_id' => $validate['flat'], 'role' => $validate['role']]))
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
            } catch (Throwable $throwable) {
                if ($throwable instanceof DatabaseException && $throwable->isUniqueViolation())
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
            }
        }

        return user_response(data: $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Put('/link')]
    public function updateSubscriberToFlatGroup(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        $db = container(DatabaseService::class);

        $result = [];

        foreach ($body as $item) {
            try {
                $validate = validator($item, ['subscriber' => rule()->id(), 'flat' => rule()->id(), 'role' => rule()->required()->int()->nonNullable()]);
            } catch (Throwable) {
                continue;
            }

            try {
                if ($db->modify('UPDATE houses_flats_subscribers SET role = :role WHERE house_subscriber_id = :subscriber_id AND house_flat_id = :flat_id', ['subscriber_id' => $validate['subscriber'], 'flat_id' => $validate['flat'], 'role' => $validate['role']]))
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
            } catch (Throwable $throwable) {
                if ($throwable instanceof DatabaseException && $throwable->isUniqueViolation())
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
            }
        }

        return user_response(data: $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Delete('/link')]
    public function deleteSubscriberFromFlatGroup(ServerRequestInterface $request): Response
    {
        $body = $request->getParsedBody();

        $db = container(DatabaseService::class);

        $result = [];

        foreach ($body as $item) {
            try {
                $validate = validator($item, ['subscriber' => rule()->id(), 'flat' => rule()->id()]);

                if ($db->modify('DELETE FROM houses_flats_subscribers WHERE house_subscriber_id = :subscriber_id AND house_flat_id = :flat_id', ['subscriber_id' => $validate['subscriber'], 'flat_id' => $validate['flat']]))
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
            } catch (Throwable) {
            }
        }

        return user_response(data: $result);
    }
}