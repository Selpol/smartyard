<?php

declare(strict_types=1);

namespace Selpol\Feature\Config;

use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\Internal\InternalConfigFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalConfigFeature::class)]
readonly abstract class ConfigFeature extends Feature
{
    public function getSuggestionsForIntercomConfig(): array
    {
        return [
            [
                'type' => 'namespace',
                'value' => ConfigKey::Auto->value,
                'title' => 'Автоопределение',

                'suggestions' => [
                    ['type' => 'value', 'value' => ConfigKey::AutoIs1->last(), 'title' => 'ISx1', 'assign' => ['type' => 'array']],
                    ['type' => 'value', 'value' => ConfigKey::AutoIs5->last(), 'title' => 'ISx5', 'assign' => ['type' => 'array']],
                    ['type' => 'value', 'value' => ConfigKey::AutoDks->last(), 'title' => 'Beward DKS', 'assign' => ['type' => 'array']],
                    ['type' => 'value', 'value' => ConfigKey::AutoDs->last(), 'title' => 'Beward DS', 'assign' => ['type' => 'array']],
                    ['type' => 'value', 'value' => ConfigKey::AutoHik->last(), 'title' => 'HikVision', 'assign' => ['type' => 'array']],

                    ['type' => 'value', 'value' => ConfigKey::AutoCamera->last(), 'title' => 'Модель камеры', 'assign' => ['type' => 'array']],

                    [
                        'type' => 'namespace',
                        'value' => ConfigKey::AutoTemplate->last(),
                        'title' => 'Шаблоны',

                        'suggestions' => [
                            ['type' => 'value', 'value' => ConfigKey::AutoTemplateDvr->last(), 'title' => 'Шаблон для Dvr', 'assign' => ['type' => 'string']],
                            ['type' => 'value', 'value' => ConfigKey::AutoTemplatePrimary->last(), 'title' => 'Шаблон для основного потока', 'assign' => ['type' => 'string']],
                            ['type' => 'value', 'value' => ConfigKey::AutoTemplateSecondary->last(), 'title' => 'Шаблон для дополнительного потока', 'assign' => ['type' => 'string']],
                        ]

                    ],
                ]
            ],

            [
                'type' => 'value',
                'value' => ConfigKey::Auth->value,
                'title' => 'Авторизация',
                'assign' => ['default' => 'basic', 'type' => 'string', 'condition' => 'in:basic,digest,any_safe'],

                'suggestions' => [
                    ['type' => 'value', 'value' => ConfigKey::AuthLogin->last(), 'title' => 'Логин', 'assign' => ['default' => 'admin', 'type' => 'string']],
                    ['type' => 'value', 'value' => ConfigKey::AuthPassword->last(), 'title' => 'Пароль', 'assign' => ['type' => 'string:env']]
                ]
            ],

            ['type' => 'value', 'value' => ConfigKey::Debug->value, 'title' => 'Дебаг', 'assign' => ['default' => 'false', 'type' => 'bool']],
            ['type' => 'value', 'value' => ConfigKey::Prepare->value, 'title' => 'Подготовка запроса', 'assign' => ['default' => '1', 'in:0,1,2']],
            ['type' => 'value', 'value' => ConfigKey::Timeout->value, 'title' => 'Ожидание, перед выполнением запроса в 1/1000000 секунд', 'assign' => ['default' => '0', 'type' => 'int']],
            ['type' => 'value', 'value' => ConfigKey::Check->value, 'title' => 'Проверка SSL', 'assign', 'assign' => ['default' => 'true', 'type' => 'bool']],
            ['type' => 'value', 'value' => ConfigKey::Log->value, 'title' => 'Файл логов', 'assign' => ['default' => 'intercom']],

            [
                'type' => 'value',
                'value' => ConfigKey::Output->value,
                'title' => 'Реле',
                'assign' => ['default' => '1', 'type' => 'int'],

                'suggestions' => [
                    ['type' => 'value', 'value' => ConfigKey::OutputMap->last(), 'title' => 'Карта реле', 'assign' => ['default' => '0:2']],
                    ['type' => 'value', 'value' => ConfigKey::OutputInvert->last(), 'title' => 'Инверт реле', 'assign' => ['default' => 'false', 'type' => 'bool']],
                ]
            ],

            [
                'type' => 'value',
                'value' => ConfigKey::Handler->value,
                'title' => 'Класс обработчик',
                'assign' => ['example' => 'Selpol\Device\Ip\Intercom\Is\IsIntercom,Selpol\Device\Ip\Intercom\Is\Is5Intercom,Selpol\Device\Ip\Intercom\Beward\DsIntercom,Selpol\Device\Ip\Intercom\Beward\DksIntercom,Selpol\Device\Ip\Intercom\HikVision\HikVisionIntercom,Selpol\Device\Ip\Intercom\Relay\RelayIntercom']
            ],

            [
                'type' => 'namespace',
                'value' => ConfigKey::Clean->value,
                'title' => 'Очистка',

                'suggestions' => [
                    ['type' => 'value', 'value' => ConfigKey::CleanUnlockTime->last(), 'title' => 'Время открытия', 'assign' => ['default' => '5', 'type' => 'int', 'condition' => 'between:5,30']],

                    ['type' => 'value', 'value' => ConfigKey::CleanCallTimeout->last(), 'title' => 'Таймаут вызова', 'assign' => ['default' => '30', 'type' => 'int', 'condition' => 'between:15,120']],
                    ['type' => 'value', 'value' => ConfigKey::CleanTalkTimeout->last(), 'title' => 'Таймаут разговора', 'assign' => ['default' => '60', 'type' => 'int', 'condition' => 'between:15,120']],

                    ['type' => 'value', 'value' => ConfigKey::CleanSos->last(), 'title' => 'SOS', 'assign' => ['default' => 'SOS']],
                    ['type' => 'value', 'value' => ConfigKey::CleanConcierge->last(), 'title' => 'Консьерж', 'assign' => ['default' => '9999']],

                    ['type' => 'value', 'value' => ConfigKey::CleanNtp->last(), 'title' => 'Сервер времени', 'assign' => ['default' => '9999', 'type' => 'string:url']],
                    ['type' => 'value', 'value' => ConfigKey::CleanSyslog->last(), 'title' => 'Сервер логов', 'assign' => ['default' => 'syslog://127.0.0.1:514', 'type' => 'string:url']],
                ]
            ],

            [
                'type' => 'namespace',
                'value' => ConfigKey::Apartment->value,
                'title' => 'Квартира',

                'suggestions' => [
                    ['type' => 'value', 'value' => ConfigKey::ApartmentAnswer->last(), 'title' => 'Уровень поднятия', ['type' => 'int']],
                    ['type' => 'value', 'value' => ConfigKey::ApartmnetQuiescent->last(), 'title' => 'Уровень ответа', ['type' => 'int']]
                ]
            ],

            [
                'type' => 'namespace',
                'value' => ConfigKey::Audio->value,
                'title' => 'Аудио',

                'suggestions' => [
                    [
                        'type' => 'value',
                        'value' => ConfigKey::AudioVolume->last(),
                        'title' => 'Звук домофона',

                        'assign' => ['type' => 'array:int'],

                        'suggestions' => [['type' => 'variable', 'value' => 'flat', 'title' => 'Звук квартиры', 'assign' => ['type' => 'array:int']]]
                    ]
                ]
            ],

            [
                'type' => 'namespace',
                'value' => ConfigKey::Video->value,
                'title' => 'Видео',

                'suggestions' => [
                    ['type' => 'value', 'value' => ConfigKey::VideoQuality->last(), 'title' => 'Разрешение', 'assign' => ['example' => '1280x720,1920x1080,1']],

                    ['type' => 'value', 'value' => ConfigKey::VideoPrimaryBitrate->last(), 'title' => 'Основной битрейт', 'assign' => ['example' => '512,1024,1536,2048']],
                    ['type' => 'value', 'value' => ConfigKey::VideoSecondaryBitrate->last(), 'title' => 'Дополнительный битрейт', 'assign' => ['example' => '512,1024,1536,2048']],

                    [
                        'type' => 'value',
                        'value' => ConfigKey::VideoRate->last(),
                        'title' => 'Поток',
                        'suggestions' => [['type' => 'value', 'value' => ConfigKey::VideoRateOffset->last(), 'title' => 'Отклонение']]
                    ],
                ]
            ],

            [
                'type' => 'namespace',
                'value' => ConfigKey::Display->value,
                'title' => 'Дисплей',

                'suggestions' => [['type' => 'value', 'value' => ConfigKey::DisplayTitle->last(), 'title' => 'Текст', 'assign' => ['default' => '%entrance%']]]
            ],

            [
                'type' => 'namespace',
                'value' => ConfigKey::Cms->value,
                'title' => 'КМС',

                'suggestions' => [['type' => 'value', 'value' => ConfigKey::CmsValue->last(), 'title' => 'Список КМС моделей', 'assign' => ['type' => 'array:string', 'example' => 'bk-100,com-100u,com-220u,com-25u,kad2501,kkm-100s2,kkm-105,km100-7.1,km100-7.5,kmg-100']]]
            ],

            [
                'type' => 'namespace',
                'value' => ConfigKey::Sip->value,
                'title' => 'SIP',

                'suggestions' => [
                    ['type' => 'value', 'value' => ConfigKey::SipStream->last(), 'title' => 'Видеопоток', 'assign' => ['condition' => 'in:0,1']],

                    ['type' => 'value', 'value' => ConfigKey::SipCall->last(), 'title' => 'Звонок в SIP', 'assign' => ['type' => 'bool', 'default' => 'false']],
                    ['type' => 'value', 'value' => ConfigKey::SipDtmf->last(), 'title' => 'DTMF Номер', 'assign' => ['type' => 'int', 'condition' => 'in:-1,*,#,1,2,3,4,5,6,7,8,9,10']],
                    ['type' => 'value', 'value' => ConfigKey::SipSos->last(), 'title' => 'SOS Номер', 'assign' => ['type' => 'int']],

                    [
                        'type' => 'namespace',
                        'value' => ConfigKey::SipNumber->last(),
                        'title' => 'Дополнительные номера телефонов',

                        'suggestions' => [
                            ['type' => 'variable', 'value' => 'flat', 'title' => 'Квартира', 'assign' => ['example' => '1000000001,1000000002']]
                        ]
                    ]
                ]
            ],

            [
                'type' => 'namespace',
                'value' => ConfigKey::Wicket->value,
                'title' => 'Калитка',

                'suggestions' => [
                    ['type' => 'value', 'value' => ConfigKey::WicketMode->last(), 'title' => 'Режим калитки для BEWARD', 'assign' => ['default' => '1', 'type' => 'int', 'condition' => 'in:1,2']]
                ]
            ],

            [
                'type' => 'value',
                'value' => ConfigKey::Mifare->value,
                'title' => 'MIFARE',

                'assign' => ['type' => 'bool'],

                'suggestions' => [
                    ['type' => 'value', 'value' => ConfigKey::MifareKey->last(), 'title' => 'Ключ', 'assign' => ['default' => 'ENV_MIFARE_KEY', 'type' => 'string:env']],
                    ['type' => 'value', 'value' => ConfigKey::MifareSector->last(), 'title' => 'Сектор', 'assign' => ['default' => 'ENV_MIFARE_SECTOR', 'type' => 'int:env']],
                    ['type' => 'value', 'value' => ConfigKey::MifareCgi->last(), 'title' => 'CGI для BEWARD', 'assign' => ['default' => 'mifareusr_cgi', 'condition' => 'in:mifareusr_cgi,mifare_cgi']]
                ]
            ],

            [
                'type' => 'value',
                'value' => ConfigKey::Gsm->value,
                'title' => 'Тип GSM',

                'suggestions' => [
                    [
                        'type' => 'variable',
                        'value' => 'gsm',
                        'title' => 'Тип GSM',

                        'suggestions' => [
                            ['type' => 'value', 'value' => ConfigKey::GsmAdd->last(), 'title' => 'Добавление номера', 'assign' => ['type' => 'string']],
                            ['type' => 'value', 'value' => ConfigKey::GsmRemove->last(), 'title' => 'Удаление номера', 'assign' => ['type' => 'string']]
                        ]
                    ],
                ]
            ]
        ];
    }

    public function getSuggestionsForCameraConfig(): array
    {
        return [
            [
                'type' => 'value',
                'value' => ConfigKey::Auth->value,
                'title' => 'Авторизация',
                'assign' => ['default' => 'basic', 'type' => 'string', 'condition' => 'in:basic,digest,any_safe'],

                'suggestions' => [
                    ['type' => 'value', 'value' => ConfigKey::AuthLogin->last(), 'title' => 'Логин', 'assign' => ['default' => 'admin', 'type' => 'string']]
                ]
            ],

            ['type' => 'value', 'value' => ConfigKey::Debug->value, 'title' => 'Дебаг', 'assign' => ['default' => 'false', 'type' => 'bool']],
            ['type' => 'value', 'value' => ConfigKey::Log->value, 'title' => 'Файл логов', 'assign' => ['default' => 'camera']],

            [
                'type' => 'value',
                'value' => ConfigKey::Handler->value,
                'title' => 'Класс обработчик',
                'assign' => ['example' => 'Selpol\Device\Ip\Camera\Is\IsCamera,Selpol\Device\Ip\Camera\Beward\BewardCamera,Selpol\Device\Ip\Camera\HikVision\HikVisionCamera,Selpol\Device\Ip\Camera\Fake\FakeCamera']
            ],

            ['type' => 'value', 'value' => ConfigKey::Screenshot->value, 'title' => 'Скриншот', 'assign' => ['type' => 'string']]
        ];
    }

    public abstract function getCacheConfigForIntercom(int $id): ?Config;

    public abstract function getCacheConfigForCamera(int $id): ?Config;

    public abstract function setCacheConfigForIntercom(Config $config, int $id): void;

    public abstract function setCacheConfigForCamera(Config $config, int $id): void;

    public abstract function clearCacheConfigForIntercom(?int $id = null): void;

    public abstract function clearCacheConfigForCamera(?int $id = null): void;

    public abstract function getConfigForIntercom(IntercomModel $model, DeviceIntercom $intercom): Config;

    public abstract function getConfigForCamera(CameraModel $model, DeviceCamera $camera): Config;

    public abstract function getOptimizeConfigForIntercom(IntercomModel $model, DeviceIntercom $intercom): Config;

    public abstract function getOptimizeConfigForCamera(CameraModel $model, DeviceCamera $camera): Config;
}
