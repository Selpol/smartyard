<?php

namespace Selpol\Controller\Api\task;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Task\Tasks\Contractor\ContractorSyncTask;
use Selpol\Task\Tasks\Frs\FrsAddStreamTask;
use Selpol\Task\Tasks\Frs\FrsRemoveStreamTask;
use Selpol\Task\Tasks\Inbox\InboxFlatTask;
use Selpol\Task\Tasks\Inbox\InboxSubscriberTask;
use Selpol\Task\Tasks\Intercom\Cms\IntercomSetCmsTask;
use Selpol\Task\Tasks\Intercom\Cms\IntercomSyncCmsTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomCmsFlatTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomDeleteFlatTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomSyncFlatTask;
use Selpol\Task\Tasks\Intercom\IntercomBlockTask;
use Selpol\Task\Tasks\Intercom\IntercomConfigureTask;
use Selpol\Task\Tasks\Intercom\IntercomEntranceTask;
use Selpol\Task\Tasks\Intercom\IntercomLevelTask;
use Selpol\Task\Tasks\Intercom\IntercomLockTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomAddKeyTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomDeleteKeyTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomHouseKeyTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomKeysKeyTask;
use Selpol\Task\Tasks\Migration\MigrationDownTask;
use Selpol\Task\Tasks\Migration\MigrationUpTask;
use Selpol\Task\Tasks\Plog\PlogCallTask;
use Selpol\Task\Tasks\Plog\PlogOpenTask;
use Selpol\Task\Tasks\QrTask;
use Selpol\Task\Tasks\RecordTask;

readonly class tasks extends Api
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function GET($params): array|ResponseInterface
    {
        return self::success([
            'high' => [
                IntercomConfigureTask::class => '[Домофон] Синхронизация домофона',
                ContractorSyncTask::class => '[Подрядчик] Синхронизация подрядчика',

                MigrationUpTask::class => '[База данных] Миграция базы данных +',
                MigrationDownTask::class => '[База данных] Миграция базы данных -'
            ],
            'default' => [
                PlogCallTask::class => '[События] Звонок',
                PlogOpenTask::class => '[События] Открытие двери',

                QrTask::class => '[Дом] Генерация QR кодов',
                RecordTask::class => '[Архив] Экспорт архива',

                FrsAddStreamTask::class => '[FRS] Добавление потока',
                FrsRemoveStreamTask::class => '[FRS] Удаление потока',

                InboxFlatTask::class => '[Уведомления] Отправка уведомления квартире',
                InboxSubscriberTask::class => '[Уведомления] Отправка уведомления абоненту',

                IntercomEntranceTask::class => '[Домофон] Синхронизация входа',
                IntercomLevelTask::class => '[Домофон] Синхронизация уровней домофона',
                IntercomLockTask::class => '[Домофон] Синхронизация замка',

                IntercomSetCmsTask::class => '[Домофон] Установка CMS',
                IntercomSyncCmsTask::class => '[Домофон] Синхронизация CMS',

                IntercomCmsFlatTask::class => '[Домофон] Синхронизация КМС Трубки',
                IntercomDeleteFlatTask::class => '[Домофон] Удаление квартиры',
                IntercomSyncFlatTask::class => '[Домофон] Синхронизация квартиры',
                IntercomBlockTask::class => '[Домофон] Синхронизация блокировок КМС Трубок',

                IntercomAddKeyTask::class => '[Домофон] Добавление ключ',
                IntercomDeleteKeyTask::class => '[Домофон] Удаление ключ',
                IntercomHouseKeyTask::class => '[Домофон] Синхронизация ключей на дому',
                IntercomKeysKeyTask::class => '[Домофон] Массовая синхронизация ключей на дому'
            ]
        ]);
    }

    public static function index(): array
    {
        return ['GET' => '[Задачи] Получить список задач'];
    }
}