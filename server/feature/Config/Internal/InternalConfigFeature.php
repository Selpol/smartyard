<?php declare(strict_types=1);

namespace Selpol\Feature\Config\Internal;

use Selpol\Entity\Repository\Core\CoreVarRepository;
use Selpol\Feature\Config\Config;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Feature\Config\ConfigItem;
use Selpol\Feature\Config\ConfigValue;

readonly class InternalConfigFeature extends ConfigFeature
{
    public function getConfigForIntercomArray(): array
    {
        return [
            new ConfigItem('auth', '[Устройство] Авторизация', 'intercom', new ConfigValue('basic', condition: 'in:basic,digest,any_safe'), ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('clean.unlock_time', '[Очистка] Время открытия', 'intercom', new ConfigValue('5', 'int', condition: 'between:5,30'), ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('clean.call_timeout', '[Очистка] Таймаут вызова', 'intercom', new ConfigValue('30', 'int', condition: 'between:15,120'), ['id', 'device_model', 'model_vendor', 'model_title']),
            new ConfigItem('clean.talk_timeout', '[Очистка] Таймаут разговора', 'intercom', new ConfigValue('60', 'int', condition: 'between:15,120'), ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('clean.sos', '[Очистка] SOS', 'intercom', new ConfigValue('SOS'), ['id', 'device_model', 'model_vendor', 'model_title']),
            new ConfigItem('clean.concierge', '[Очистка] Консьерж', 'intercom', new ConfigValue('9999', 'int'), ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('clean.ntp', '[Очистка] Сервер времени', 'intercom', new ConfigValue(), ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('audio.volume', '[Аудио] Звук домофона', 'intercom', new ConfigValue(type: 'array:int'), ['id', 'device_model', 'model_vendor', 'model_title']),
            new ConfigItem('audio.volume', '[Аудио] Звук домофона на квартире', null, new ConfigValue(type: 'array:int'), ['flat']),

            new ConfigItem('video.quality', '[Видео] Разрешение', 'intercom', new ConfigValue(example: '1920x1080,1'), ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('video.primary_bitrate', '[Видео] Основной битрейт', 'intercom', new ConfigValue(type: 'int', condition: 'in:512,1024,1536,2048'), ['id', 'device_model', 'model_vendor', 'model_title']),
            new ConfigItem('video.secondary_bitrate', '[Видео] Дополнительный битрейт', 'intercom', new ConfigValue(type: 'int', condition: 'in:512,1024,1536,2048'), ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('display.title', '[Дисплей] Текст', 'intercom', new ConfigValue('${entrance}'), ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('mifare', '[Ключи] MIFARE', 'intercom', new ConfigValue('true', 'bool'), ['id', 'device_model', 'model_vendor', 'model_title']),
            new ConfigItem('mifare.key', '[Ключи] MIFARE Ключ', 'intercom', new ConfigValue('ENV_MIFARE_KEY', 'env,string'), ['id', 'device_model', 'model_vendor', 'model_title']),
            new ConfigItem('mifare.sector', '[Ключи] MIFARE Сектор', 'intercom', new ConfigValue('ENV_MIFARE_SECTOR', 'env,int'), ['id', 'device_model', 'model_vendor', 'model_title']),
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