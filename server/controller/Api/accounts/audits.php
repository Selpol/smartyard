<?php

namespace Selpol\Controller\Api\accounts;

use Selpol\Entity\Model\Block\FlatBlock;
use Selpol\Entity\Model\Block\SubscriberBlock;
use Selpol\Entity\Model\Contractor;
use Selpol\Entity\Model\Core\CoreAuth;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Entity\Model\Core\CoreVar;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Entity\Model\Dvr\DvrServer;
use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Entity\Model\Role;
use Selpol\Entity\Model\Server\StreamerServer;
use Selpol\Entity\Model\Sip\SipServer;
use Selpol\Entity\Model\Sip\SipUser;
use Selpol\Feature\Group\Group;
use Selpol\Task\Tasks\Contractor\ContractorSyncTask;
use Selpol\Task\Tasks\Frs\FrsAddStreamTask;
use Selpol\Task\Tasks\Frs\FrsRemoveStreamTask;
use Selpol\Task\Tasks\Intercom\Cms\IntercomSetCmsTask;
use Selpol\Task\Tasks\Intercom\Cms\IntercomSyncCmsTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomCmsFlatTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomDeleteFlatTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomSyncFlatTask;
use Selpol\Task\Tasks\Intercom\IntercomConfigureTask;
use Selpol\Task\Tasks\Intercom\IntercomEntranceTask;
use Selpol\Task\Tasks\Intercom\IntercomLevelTask;
use Selpol\Task\Tasks\Intercom\IntercomBlockTask;
use Selpol\Task\Tasks\Intercom\IntercomLockTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomHouseKeyTask;
use Selpol\Task\Tasks\Intercom\Key\IntercomKeysKeyTask;
use Selpol\Task\Tasks\QrTask;
use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class audits extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        return self::success([
            FlatBlock::class => 'Блокировка-Квартира',
            SubscriberBlock::class => 'Блокировка-Абонент',
            Contractor::class => 'Подрядчик',
            CoreAuth::class => 'Пользователь-Авторизация',
            CoreUser::class => 'Пользователь',
            CoreVar::class => 'Переменная',
            DeviceCamera::class => 'Камера',
            DeviceIntercom::class => 'Домофон',
            DvrServer::class => 'Сервер-Dvr',
            FrsServer::class => 'Сервер-Frs',
            HouseFlat::class => 'Квартира',
            HouseKey::class => 'Ключ',
            HouseSubscriber::class => 'Абонент',
            Role::class => 'Роль',
            StreamerServer::class => 'Сервер-Стример',
            SipServer::class => 'Сервер-Sip',
            SipUser::class => 'Sip-Пользователь',
            Group::class => 'Группа',
            ContractorSyncTask::class => 'Задача синхронизации подрядчика',
            FrsAddStreamTask::class => 'Задача добавление потока на frs',
            FrsRemoveStreamTask::class => 'Задача удаление потока на frs',
            IntercomSetCmsTask::class => 'Задача установки КМС на домофон',
            IntercomSyncCmsTask::class => 'Задача синхронизации домофона',
            IntercomCmsFlatTask::class => 'Задача устровки КМС квартире на домофоне',
            IntercomDeleteFlatTask::class => 'Задача удаление квартиры с домофона',
            IntercomSyncFlatTask::class => 'Задача синхронизации квартиры на домофоне',
            IntercomConfigureTask::class => 'Задача синхронизации домофона',
            IntercomEntranceTask::class => 'Задача синхронизации входа',
            IntercomLevelTask::class => 'Задача синхронизации уровня на домофоне',
            IntercomLockTask::class => 'Задача синхронизации реле на домофоне',
            IntercomBlockTask::class => 'Задача синхронизации блокировок КМС Трубок',
            IntercomHouseKeyTask::class => 'Задача синхронизации ключей дома',
            IntercomKeysKeyTask::class => 'Задача синхронизация ключей',
            QrTask::class => 'Задача генерации QR-кода',
        ]);
    }

    public static function index(): array
    {
        return ['GET' => '[Пользователь] Получить список типов действий'];
    }
}