<?php

namespace Selpol\Controller\Api\accounts;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class audits extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        return self::success([
            'Selpol\\Entity\\Model\\Block\\FlatBlock' => 'Блокировка-Квартира',
            'Selpol\\Entity\\Model\\Block\\SubscriberBlock' => 'Блокировка-Абонент',
            'Selpol\\Entity\\Model\\Contractor' => 'Подрядчик',
            'Selpol\\Entity\\Model\\Core\\CoreAuth' => 'Пользователь-Авторизация',
            'Selpol\\Entity\\Model\\Core\\CoreUser' => 'Пользователь',
            'Selpol\\Entity\\Model\\Core\\CoreVar' => 'Переменная',
            'Selpol\\Entity\\Model\\Device\\DeviceCamera' => 'Камера',
            'Selpol\\Entity\\Model\\Device\\DeviceIntercom' => 'Домофон',
            'Selpol\\Entity\\Model\\Dvr\\DvrServer' => 'Сервер-Dvr',
            'Selpol\\Entity\\Model\\Frs\\FrsServer' => 'Сервер-Frs',
            'Selpol\\Entity\\Model\\House\\HouseFlat' => 'Квартира',
            'Selpol\\Entity\\Model\\House\\HouseKey' => 'Ключ',
            'Selpol\\Entity\\Model\\House\\HouseSubscriber' => 'Абонент',
            'Selpol\\Entity\\Model\\Role' => 'Роль',
            'Selpol\\Entity\\Model\\Server\\StreamerServer' => 'Сервер-Стример',
            'Selpol\\Entity\\Model\\Sip\\SipServer' => 'Сервер-Sip',
            'Selpol\\Entity\\Model\\Sip\\SipUser' => 'Sip-Пользователь',
            'Selpol\\Feature\\Group\\Group' => 'Группа',
            'Selpol\\Task\\Tasks\\ContractTask' => 'Задача подрядчика',
            'Selpol\\Task\\Tasks\\Contractor\\ContractorSyncTask' => 'Задача синхронизации подрядчика',
            'Selpol\\Task\\Tasks\\Frs\\FrsAddStreamTask' => 'Задача добавление потока на frs',
            'Selpol\\Task\\Tasks\\Frs\\FrsRemoveStreamTask' => 'Задача удаление потока на frs',
            'Selpol\\Task\\Tasks\\Intercom\\Cms\\IntercomSetCmsTask' => 'Задача установки КМС на домофон',
            'Selpol\\Task\\Tasks\\Intercom\\Cms\\IntercomSyncCmsTask' => 'Задача синхронизации домофона',
            'Selpol\\Task\\Tasks\\Intercom\\Flat\\IntercomCmsFlatTask' => 'Задача устровки КМС квартире на домофоне',
            'Selpol\\Task\\Tasks\\Intercom\\Flat\\IntercomDeleteFlatTask' => 'Задача удаление квартиры с домофона',
            'Selpol\\Task\\Tasks\\Intercom\\Flat\\IntercomSyncFlatTask' => 'Задача синхронизации квартиры на домофоне',
            'Selpol\\Task\\Tasks\\Intercom\\IntercomConfigureTask' => 'Задача синхронизации домофона',
            'Selpol\\Task\\Tasks\\Intercom\\IntercomEntranceTask' => 'Задача синхронизации входа',
            'Selpol\\Task\\Tasks\\Intercom\\IntercomLevelTask' => 'Задача синхронизации уровня на домофоне',
            'Selpol\\Task\\Tasks\\Intercom\\IntercomUnlockTask' => 'Задача синхронизации реле на домофоне',
            'Selpol\\Task\\Tasks\\Intercom\\IntercomBlockTask' => 'Задача синхронизации блокировок КМС Трубок',
            'Selpol\\Task\\Tasks\\Intercom\\Key\\IntercomHouseKeyTask' => 'Задача синхронизации ключей дома',
            'Selpol\\Task\\Tasks\\Intercom\\Key\\IntercomKeysKeyTask' => 'Задача синхронизация ключей',
            'Selpol\\Task\\Tasks\\QrTask' => 'Задача генерации QR-кода',
        ]);
    }

    public static function index(): array
    {
        return ['GET' => '[Пользователь] Получить список типов действий'];
    }
}