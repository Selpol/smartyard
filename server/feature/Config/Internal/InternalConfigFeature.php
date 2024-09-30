<?php declare(strict_types=1);

namespace Selpol\Feature\Config\Internal;

use Selpol\Entity\Repository\Core\CoreVarRepository;
use Selpol\Feature\Config\Config;
use Selpol\Feature\Config\ConfigFeature;
use Selpol\Feature\Config\ConfigItem;

readonly class InternalConfigFeature extends ConfigFeature
{
    public function getConfigForIntercomArray(): array
    {
        return [
            new ConfigItem('auth', '[Устройство] Авторизация', 'basic', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('clean.unlock_time', '[Очистка] Время открытия', '5', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('clean.call_timeout', '[Очистка] Таймаут вызова', '30', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),
            new ConfigItem('clean.talk_timeout', '[Очистка] Таймаут разговора', '60', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('clean.sos', '[Очистка] SOS', 'SOS', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),
            new ConfigItem('clean.concierge', '[Очистка] Консьерж', '9999', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('clean.ntp', '[Очистка] Сервер времени', '', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('audio.volume', '[Аудио] Звук домофона', '', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),
            new ConfigItem('audio.volume', '[Аудио] Звук домофона на квартире', '', null, ['flat']),

            new ConfigItem('video.primary_bitrate', '[Видео] Основной битрейт', '', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),
            new ConfigItem('video.secondary_bitrate', '[Видео] Дополнительный битрейт', '', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),

            new ConfigItem('mifare', '[Ключи] MIFARE', 'true', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),
            new ConfigItem('mifare.key', '[Ключи] MIFARE Ключ', 'ENV_MIFARE_KEY', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),
            new ConfigItem('mifare.sector', '[Ключи] MIFARE Сектор', 'ENV_MIFARE_SECTOR', 'intercom', ['id', 'device_model', 'model_vendor', 'model_title']),
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