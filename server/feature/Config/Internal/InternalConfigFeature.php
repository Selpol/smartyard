<?php declare(strict_types=1);

namespace Selpol\Feature\Config\Internal;

use Selpol\Entity\Repository\Core\CoreVarRepository;
use Selpol\Feature\Config\Config;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Feature\Config\ConfigItem;
use Selpol\Feature\Config\ConfigValue;

readonly class InternalConfigFeature extends ConfigFeature
{
    public function getDescriptionForIntercomConfig(): array
    {
        return [
            new ConfigItem('debug', '[Устройство] Дебаг', new ConfigValue('false', 'bool')),
            new ConfigItem('auth', '[Устройство] Авторизация', new ConfigValue('basic', condition: 'in:basic,digest,any_safe')),

            new ConfigItem('clean.unlock_time', '[Очистка] Время открытия', new ConfigValue('5', 'int', condition: 'between:5,30')),

            new ConfigItem('clean.call_timeout', '[Очистка] Таймаут вызова', new ConfigValue('30', 'int', condition: 'between:15,120')),
            new ConfigItem('clean.talk_timeout', '[Очистка] Таймаут разговора', new ConfigValue('60', 'int', condition: 'between:15,120')),

            new ConfigItem('clean.sos', '[Очистка] SOS', new ConfigValue('SOS')),
            new ConfigItem('clean.concierge', '[Очистка] Консьерж', new ConfigValue('9999', 'int')),

            new ConfigItem('clean.ntp', '[Очистка] Сервер времени', new ConfigValue()),

            new ConfigItem('audio.volume', '[Аудио] Звук домофона', new ConfigValue(type: 'array:int'), ['flat']),

            new ConfigItem('video.quality', '[Видео] Разрешение', new ConfigValue(example: '1920x1080,1')),

            new ConfigItem('video.primary_bitrate', '[Видео] Основной битрейт', new ConfigValue(type: 'int', condition: 'in:512,1024,1536,2048')),
            new ConfigItem('video.secondary_bitrate', '[Видео] Дополнительный битрейт', new ConfigValue(type: 'int', condition: 'in:512,1024,1536,2048')),

            new ConfigItem('display.title', '[Дисплей] Текст', new ConfigValue('${entrance}')),

            new ConfigItem('mifare', '[Ключи] MIFARE', new ConfigValue('true', 'bool')),
            new ConfigItem('mifare.key', '[Ключи] MIFARE Ключ', new ConfigValue('ENV_MIFARE_KEY', 'env,string')),
            new ConfigItem('mifare.sector', '[Ключи] MIFARE Сектор', new ConfigValue('ENV_MIFARE_SECTOR', 'env,int')),
        ];
    }

    public function getConfigForIntercom(): Config
    {
        $value = new Config();

        $coreVar = container(CoreVarRepository::class)->findByName('intercom.config');

        if ($coreVar && $coreVar->var_value) {
            $value->load($coreVar->var_value);
        }

        return $value;
    }
}