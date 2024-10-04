<?php declare(strict_types=1);

namespace Selpol\Feature\Config;

use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Config\Internal\InternalConfigFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalConfigFeature::class)]
readonly abstract class ConfigFeature extends Feature
{
    public function getDescriptionForIntercomConfig(): array
    {
        return [
            new ConfigItem('debug', '[Устройство] Дебаг', new ConfigValue('false', 'bool')),
            new ConfigItem('auth', '[Устройство] Авторизация', new ConfigValue('basic', condition: 'in:basic,digest,any_safe')),

            new ConfigItem('output', '[Устройство] Количество выходов', new ConfigValue('1')),

            new ConfigItem('clean.unlock_time', '[Очистка] Время открытия', new ConfigValue('5', 'int', condition: 'between:5,30')),

            new ConfigItem('clean.call_timeout', '[Очистка] Таймаут вызова', new ConfigValue('30', 'int', condition: 'between:15,120')),
            new ConfigItem('clean.talk_timeout', '[Очистка] Таймаут разговора', new ConfigValue('60', 'int', condition: 'between:15,120')),

            new ConfigItem('clean.sos', '[Очистка] SOS', new ConfigValue('SOS')),
            new ConfigItem('clean.concierge', '[Очистка] Консьерж', new ConfigValue('9999', 'int')),

            new ConfigItem('clean.ntp', '[Очистка] Сервер времени', new ConfigValue()),
            new ConfigItem('clean.syslog', '[Очистка] Сервер логов', new ConfigValue('syslog://127.0.0.1:514')),

            new ConfigItem('audio.volume', '[Аудио] Звук домофона', new ConfigValue(type: 'array:int'), ['flat']),

            new ConfigItem('video.quality', '[Видео] Разрешение', new ConfigValue(example: '1280x720,1920x1080,1')),

            new ConfigItem('video.primary_bitrate', '[Видео] Основной битрейт', new ConfigValue(type: 'int', condition: 'in:512,1024,1536,2048')),
            new ConfigItem('video.secondary_bitrate', '[Видео] Дополнительный битрейт', new ConfigValue(type: 'int', condition: 'in:512,1024,1536,2048')),

            new ConfigItem('display.title', '[Дисплей] Текст', new ConfigValue('${entrance}')),

            new ConfigItem('cms.value', '[КМС] Список КМС моделей', new ConfigValue('', example: 'bk-100,com-100u,com-220u,com-25u,kad2501,kkm-100s2,kkm-105,km100-7.1,km100-7.5,kmg-100')),

            new ConfigItem('sip.stream', '[SIP] Видеопоток', new ConfigValue('0', condition: 'in:0,1')),

            new ConfigItem('mifare', '[Ключи] MIFARE', new ConfigValue('true', 'bool')),
            new ConfigItem('mifare.key', '[Ключи] MIFARE Ключ', new ConfigValue('ENV_MIFARE_KEY', 'env,string')),
            new ConfigItem('mifare.sector', '[Ключи] MIFARE Сектор', new ConfigValue('ENV_MIFARE_SECTOR', 'env,int')),
        ];
    }

    public abstract function clearConfigForIntercom(?int $id = null): void;

    public abstract function getConfigForIntercom(IntercomModel $model, DeviceIntercom $intercom, bool $cache = true): Config;
}