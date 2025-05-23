<?php

namespace Selpol\Controller\Internal;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selpol\Entity\Model\Block\FlatBlock;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Framework\Router\Route\RouteController;
use Selpol\Service\DatabaseService;
use Selpol\Service\Exception\DatabaseException;
use Selpol\Task\Tasks\Inbox\InboxFlatTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomCmsFlatTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomCodeFlatTask;
use Throwable;

/**
 * Синхронизация
 */
#[Controller('/internal/sync')]
readonly class SyncController extends RouteController
{
    /**
     * Получить список домов
     * @throws NotFoundExceptionInterface
     */
    #[Post('/house')]
    public function getHouseGroup(ServerRequestInterface $request, DatabaseService $databaseService): Response
    {
        $body = $request->getParsedBody();

        $houses = $databaseService->get('SELECT address_house_id, house_uuid FROM addresses_houses WHERE house_uuid IN (' . implode(', ', array_map(static fn(string $value): string => "'" . $value . "'", $body)) . ')');

        $result = [];

        if (count($houses) > 0) {
            $counter = count($houses);
            for ($i = 0; $i < $counter; ++$i)
                $result[$houses[$i]['house_uuid']] = [
                    'id' => $houses[$i]['address_house_id'],
                    'flats' => $databaseService->get('SELECT house_flat_id as id, flat FROM  houses_flats WHERE address_house_id = :id', ['id' => $houses[$i]['address_house_id']])
                ];

            return user_response(data: $result);
        }

        return user_response(data: []);
    }

    /**
     * Добавить абонентов
     * @throws NotFoundExceptionInterface
     */
    #[Post('/subscriber')]
    public function addSubscriberGroup(ServerRequestInterface $request, HouseFeature $houseFeature): Response
    {
        $body = $request->getParsedBody();

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
                $subscriberId = $houseFeature->addSubscriber($validate['id'], $validate['name'], $validate['patronymic'], $validate['audJti']);

                if ($subscriberId) {
                    $result[$validate['id']] = $subscriberId;
                }
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
     * Обновить абонентов
     * @throws NotFoundExceptionInterface
     */
    #[Put('/subscriber')]
    public function updateSubscriberGroup(ServerRequestInterface $request, HouseFeature $houseFeature): Response
    {
        $body = $request->getParsedBody();

        $result = [];

        foreach ($body as $subscriber) {
            try {
                $validate = validator($subscriber, [
                    'subscriberId' => rule()->id(),
                    'name' => rule()->required()->clamp(1, 32)->nonNullable(),
                    'patronymic' => rule()->required()->clamp(1, 32)->nonNullable(),
                ]);

                if ($houseFeature->modifySubscriber($validate['subscriberId'], ['subscriberName' => $validate['name'], 'subscriberPatronymic' => $validate['patronymic']])) {
                    $result[$validate['subscriberId']] = true;
                }
            } catch (Throwable) {
            }
        }

        return user_response(data: $result);
    }

    /**
     * Удалить абонентов
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
                if (HouseSubscriber::findById($item, setting: setting()->nonNullable())->safeDelete()) {
                    $result[$item] = true;
                }
            } catch (Throwable) {
            }

        return user_response(data: $result);
    }

    /**
     * Обновить квартиру
     * @throws NotFoundExceptionInterface
     */
    #[Put('/flat')]
    public function updateFlatGroup(ServerRequestInterface $request, BlockFeature $blockFeature): Response
    {
        $body = $request->getParsedBody();

        $result = [];

        foreach ($body as $item) {
            try {
                $validate = validator($item, ['id' => rule()->id(), 'intercom' => rule()->bool(), 'cctv' => rule()->bool(), 'autoBlock' => rule()->bool()]);

                $intercom = $validate['autoBlock'] !== null ? $validate['autoBlock'] : $validate['intercom'];
                $cctv = $validate['cctv'];

                if ($intercom !== null) {
                    $block = $blockFeature->getFirstBlockForFlat($validate['id'], [BlockFeature::SERVICE_INTERCOM]);

                    if ($block != null) {
                        if ($intercom) {
                            $block->status |= BlockFeature::STATUS_BILLING;
                            $block->cause = 'Заблокировано за неуплату';
                            $block->update();
                        } elseif ($block->status == BlockFeature::STATUS_BILLING) {
                            $block->delete();
                        } else {
                            $block->status = BlockFeature::STATUS_ADMIN;
                            $block->update();
                        }
                    } elseif ($intercom) {
                        $block = new FlatBlock();
                        $block->flat_id = $validate['id'];
                        $block->service = BlockFeature::SERVICE_INTERCOM;
                        $block->status = BlockFeature::STATUS_BILLING;
                        $block->cause = 'Заблокировано за неуплату';
                        $block->insert();
                    }

                    task(new IntercomCmsFlatTask($validate['id'], boolval($intercom)))->high()->async();
                    task(new IntercomCodeFlatTask($validate['id'], boolval($intercom)))->high()->async();
                    task(new InboxFlatTask($validate['id'], 'Обновление статуса квартиры', $intercom ? ('Услуга умного домофона заблокирована' . ($block?->cause ? ('. ' . $block->cause) : '')) : 'Услуга умного домофона разблокирована', 'inbox'))->low()->async();
                }

                if ($cctv !== null) {
                    $block = $blockFeature->getFirstBlockForFlat($validate['id'], [BlockFeature::SERVICE_CCTV]);

                    if ($block != null) {
                        if ($cctv) {
                            $block->status |= BlockFeature::STATUS_BILLING;
                            $block->cause = 'Заблокировано за неуплату';
                            $block->update();
                        } elseif ($block->status == BlockFeature::STATUS_BILLING) {
                            $block->delete();
                        } else {
                            $block->status = BlockFeature::STATUS_ADMIN;
                            $block->update();
                        }
                    } elseif ($cctv) {
                        $block = new FlatBlock();
                        $block->flat_id = $validate['id'];
                        $block->service = BlockFeature::SERVICE_CCTV;
                        $block->status = BlockFeature::STATUS_BILLING;
                        $block->cause = 'Заблокировано за неуплату';
                        $block->insert();
                    }

                    task(new InboxFlatTask($validate['id'], 'Обновление статуса квартиры', $cctv ? ('Услуга видеонаблюдения заблокирована' . ($block?->cause ? ('. ' . $block->cause) : '')) : 'Услуга видеонаблюдение разблокирована', 'inbox'))->low()->async();
                }

                $result[$validate['id']] = true;
            } catch (Throwable $throwable) {
                file_logger('sync')->error($throwable);
            }
        }

        return user_response(data: $result);
    }

    /**
     * Добавить абонентов в квартиру
     * @throws NotFoundExceptionInterface
     */
    #[Post('/link')]
    public function addSubscriberToFlatGroup(ServerRequestInterface $request, DatabaseService $databaseService): Response
    {
        $body = $request->getParsedBody();

        $result = [];

        foreach ($body as $item) {
            try {
                $validate = validator($item, ['subscriber' => rule()->id(), 'flat' => rule()->id(), 'role' => rule()->required()->int()->nonNullable()]);
            } catch (Throwable) {
                continue;
            }

            try {
                if ($databaseService->insert('INSERT INTO houses_flats_subscribers(house_subscriber_id, house_flat_id, role) VALUES (:subscriber_id, :flat_id, :role)', ['subscriber_id' => $validate['subscriber'], 'flat_id' => $validate['flat'], 'role' => $validate['role']])) {
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
                }
            } catch (Throwable $throwable) {
                if ($throwable instanceof DatabaseException && $throwable->isUniqueViolation()) {
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
                }
            }
        }

        return user_response(data: $result);
    }

    /**
     * Обновить абонентов в квартире
     * @throws NotFoundExceptionInterface
     */
    #[Put('/link')]
    public function updateSubscriberToFlatGroup(ServerRequestInterface $request, DatabaseService $databaseService): Response
    {
        $body = $request->getParsedBody();

        $result = [];

        foreach ($body as $item) {
            try {
                $validate = validator($item, ['subscriber' => rule()->id(), 'flat' => rule()->id(), 'role' => rule()->required()->int()->nonNullable()]);
            } catch (Throwable) {
                continue;
            }

            try {
                if ($databaseService->modify('UPDATE houses_flats_subscribers SET role = :role WHERE house_subscriber_id = :subscriber_id AND house_flat_id = :flat_id', ['subscriber_id' => $validate['subscriber'], 'flat_id' => $validate['flat'], 'role' => $validate['role']])) {
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
                }
            } catch (Throwable $throwable) {
                if ($throwable instanceof DatabaseException && $throwable->isUniqueViolation()) {
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
                }
            }
        }

        return user_response(data: $result);
    }

    /**
     * Удалить абонентов из квартиры
     * @throws NotFoundExceptionInterface
     */
    #[Delete('/link')]
    public function deleteSubscriberFromFlatGroup(ServerRequestInterface $request, DatabaseService $databaseService): Response
    {
        $body = $request->getParsedBody();

        $result = [];

        foreach ($body as $item) {
            try {
                $validate = validator($item, ['subscriber' => rule()->id(), 'flat' => rule()->id()]);

                if ($databaseService->modify('DELETE FROM houses_flats_subscribers WHERE house_subscriber_id = :subscriber_id AND house_flat_id = :flat_id', ['subscriber_id' => $validate['subscriber'], 'flat_id' => $validate['flat']])) {
                    $result[$validate['subscriber'] . '-' . $validate['flat']] = true;
                }
            } catch (Throwable) {
            }
        }

        return user_response(data: $result);
    }
}