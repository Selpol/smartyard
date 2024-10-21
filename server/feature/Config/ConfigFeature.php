<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

use Selpol\Feature\Config\Internal\InternalConfigFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalConfigFeature::class)]
readonly abstract class ConfigFeature extends Feature
{
    public function getSuggestionsForIntercomConfig(): array
    {
        return [
            ['type' => 'value', 'value' => 'debug', 'title' => 'Дебаг', 'assign' => ['default' => 'false', 'type' => 'bool']],
            ['type' => 'value', 'value' => 'auth', 'title' => 'Авторизация', 'assign' => ['default' => 'basic', 'type' => 'string', 'condition' => 'in:basic,digest,any_safe']],
            ['type' => 'value', 'value' => 'output', 'title' => 'Реле', 'assign' => ['default' => '1', 'type' => 'int']],

            [
                'type' => 'namespace',
                'value' => 'clean',
                'title' => 'Очистка',

                'suggestions' => [
                    ['type' => 'value', 'value' => 'unlock_time', 'title' => 'Время открытия', 'assign' => ['default' => '5', 'type' => 'int', 'condition' => 'between:5,30']],

                    ['type' => 'value', 'value' => 'call_timeout', 'title' => 'Таймаут вызова', 'assign' => ['default' => '30', 'type' => 'int', 'condition' => 'between:15,120']],
                    ['type' => 'value', 'value' => 'talk_timeout', 'title' => 'Таймаут разговора', 'assign' => ['default' => '60', 'type' => 'int', 'condition' => 'between:15,120']],

                    ['type' => 'value', 'value' => 'sos', 'title' => 'SOS', 'assign' => ['default' => 'SOS']],
                    ['type' => 'value', 'value' => 'concierge', 'title' => 'Консьерж', 'assign' => ['default' => '9999']],

                    ['type' => 'value', 'value' => 'ntp', 'title' => 'Сервер времени', 'assign' => ['default' => '9999', 'type' => 'string:url']],
                    ['type' => 'value', 'value' => 'syslog', 'title' => 'Сервер логов', 'assign' => ['default' => 'syslog://127.0.0.1:514', 'type' => 'string:url']],
                ]
            ],

            [
                'type' => 'namespace',
                'value' => 'apartment',
                'title' => 'Квартира',

                'suggestions' => [
                    ['type' => 'value', 'value' => 'answer', 'title' => 'Уровень поднятия', ['type' => 'int']],
                    ['type' => 'value', 'value' => 'quiescent', 'title' => 'Уровень ответа', ['type' => 'int']]
                ]
            ],

            [
                'type' => 'namespace',
                'value' => 'audio',
                'title' => 'Аудио',

                'suggestions' => [
                    [
                        'type' => 'value',
                        'value' => 'volume',
                        'title' => 'Звук домофона',

                        'assign' => ['type' => 'array:int'],

                        'suggestions' => [['type' => 'variable', 'value' => 'flat', 'title' => 'Звук квартиры', 'assign' => ['type' => 'array:int']]]
                    ]
                ]
            ],

            [
                'type' => 'namespace',
                'value' => 'video',
                'title' => 'Видео',

                'suggestions' => [
                    ['type' => 'value', 'value' => 'quality', 'title' => 'Разрешение', 'assign' => ['example' => '1280x720,1920x1080,1']],

                    ['type' => 'value', 'value' => 'primary_bitrate', 'title' => 'Основной битрейт', 'assign' => ['example' => '512,1024,1536,2048']],
                    ['type' => 'value', 'value' => 'secondary_bitrate', 'title' => 'Дополнительный битрейт', 'assign' => ['example' => '512,1024,1536,2048']],
                ]
            ],

            [
                'type' => 'namespace',
                'value' => 'display',
                'title' => 'Дисплей',

                'suggestions' => [['type' => 'value', 'value' => 'title', 'title' => 'Текст', 'assign' => ['default' => '%entrance%']]]
            ],

            [
                'type' => 'namespace',
                'value' => 'cms',
                'title' => 'КМС',

                'suggestions' => [['type' => 'value', 'value' => 'value', 'title' => 'Список КМС моделей', 'assign' => ['type' => 'array:string', 'example' => 'bk-100,com-100u,com-220u,com-25u,kad2501,kkm-100s2,kkm-105,km100-7.1,km100-7.5,kmg-100']]]
            ],

            [
                'type' => 'namespace',
                'value' => 'sip',
                'title' => 'SIP',

                'suggestions' => [
                    ['type' => 'value', 'value' => 'stream', 'title' => 'Видеопоток', 'assign' => ['condition' => 'in:0,1']],

                    [
                        'type' => 'namespace',
                        'value' => 'number',
                        'title' => 'Дополнительные номера телефонов',

                        'suggestions' => [
                            ['type' => 'variable', 'value' => 'flat', 'title' => 'Квартира', 'assign' => ['example' => '1000000001,1000000002']]
                        ]
                    ]
                ]
            ],

            [
                'type' => 'value',
                'value' => 'mifare',
                'title' => 'MIFARE',

                'assign' => ['type' => 'bool'],

                'suggestions' => [
                    ['type' => 'value', 'value' => 'key', 'title' => 'Ключ', 'assign' => ['default' => 'ENV_MIFARE_KEY', 'type' => 'string:env']],
                    ['type' => 'value', 'value' => 'sector', 'title' => 'Сектор', 'assign' => ['default' => 'ENV_MIFARE_SECTOR', 'type' => 'int:env']]
                ]
            ]
        ];
    }

    public abstract function clearCacheConfigForIntercom(?int $id = null): void;

    public abstract function getConfigForIntercom(string $model, ?string $intercom): Config;
}