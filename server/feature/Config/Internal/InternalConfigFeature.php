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
            new ConfigItem('clean.unlock_time', 'Время открытия', '5', 'intercom', ['Идентификатор', 'Модель', 'Производитель', 'Название']),

            new ConfigItem('clean.call_timeout', 'Таймаут вызова', '30', 'intercom', ['Идентификатор', 'Модель', 'Производитель', 'Название']),
            new ConfigItem('clean.talk_timeout', 'Таймаут разговора', '60', 'intercom', ['Идентификатор', 'Модель', 'Производитель', 'Название']),

            new ConfigItem('clean.sos', 'SOS', 'SOS', 'intercom', ['Идентификатор', 'Модель', 'Производитель', 'Название']),
            new ConfigItem('clean.concierge', 'Консьерж', '9999', 'intercom', ['Идентификатор', 'Модель', 'Производитель', 'Название']),

            new ConfigItem('clean.ntp', 'Сервер времени', '', 'intercom', ['Идентификатор', 'Модель', 'Производитель', 'Название']),

            new ConfigItem('audio', 'Аудио', '', 'intercom', ['Идентификатор', 'Модель', 'Производитель', 'Название']),
            new ConfigItem('audio', 'Аудио', '', null, ['Квартира']),

            new ConfigItem('auth', 'Авторизация', 'basic', 'intercom', ['Идентификатор', 'Модель', 'Производитель', 'Название']),
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