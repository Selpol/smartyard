<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\TaskDeleteRequest;
use Selpol\Controller\Request\Admin\TaskSearchRequest;
use Selpol\Entity\Model\Task;
use Selpol\Feature\Task\TaskFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Task\Tasks\Contractor\ContractorSyncTask;
use Selpol\Task\Tasks\Flat\FlatCodeDeleteTask;
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
use Selpol\Task\Tasks\Intercom\IntercomHealthTask;
use Selpol\Task\Tasks\Intercom\IntercomLevelTask;
use Selpol\Task\Tasks\Intercom\IntercomLockTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomAddKeyTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomDeleteKeyTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomFlatKeyTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomHouseKeyTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomKeysKeyTask;
use Selpol\Task\Tasks\Migration\MigrationDownTask;
use Selpol\Task\Tasks\Migration\MigrationUpTask;
use Selpol\Task\Tasks\Plog\PlogCallTask;
use Selpol\Task\Tasks\Plog\PlogOpenTask;
use Selpol\Task\Tasks\QrTask;
use Selpol\Task\Tasks\RecordTask;
use Selpol\Task\Tasks\ScheduleTask;

/**
 * Задачи
 */
#[Controller('/admin/task')]
readonly class TaskController extends AdminRbtController
{
    /**
     * Найти адрес по поиску
     */
    #[Get]
    public function index(): ResponseInterface
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
                IntercomHealthTask::class => '[Домофон] Проверка состояния здоровья',
                IntercomLockTask::class => '[Домофон] Синхронизация замка',

                IntercomAddKeyTask::class => '[Домофон] Добавление ключ',
                IntercomDeleteKeyTask::class => '[Домофон] Удаление ключ',
                IntercomFlatKeyTask::class => '[Домофон] Обновить ключи на квартире',
                IntercomHouseKeyTask::class => '[Домофон] Синхронизация ключей на дому',
                IntercomKeysKeyTask::class => '[Домофон] Массовая синхронизация ключей на дому',

                FlatCodeDeleteTask::class => '[Квартира] Удалить недельный код',

                ScheduleTask::class => '[Расписание] Повторяющая задача расписания'
            ]
        ]);
    }

    /**
     * Поиск задач
     */
    #[Get('/search')]
    public function search(TaskSearchRequest $request): ResponseInterface
    {
        $criteria = criteria()
            ->like('title', $request->title)
            ->like('message', $request->message)
            ->equal('class', $request->class)
            ->desc('created_at');

        return self::success(Task::fetchPage($request->page, $request->size, $criteria));
    }

    /**
     * Получить список всех уникальных задач
     */
    #[Get('/unique')]
    public function unique(TaskFeature $feature): ResponseInterface
    {
        return self::success($feature->getUniques());
    }

    /**
     * Удалнить значение уникальной задачи из списка
     */
    #[Delete('/unique')]
    public function delete(TaskDeleteRequest $request): ResponseInterface
    {
        if (strlen($request->key) > 12) {
            container(TaskFeature::class)->releaseUnique(substr($request->key, 12));

            return self::success();
        }

        return self::error('Не верный ключ');
    }
}