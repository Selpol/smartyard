<?php declare(strict_types=1);

namespace Selpol\Cli\Subscriber;

use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\Oauth\OauthFeature;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Cli\IO\CliIO;

#[Executable('subscriber:aud', 'Обновление поля авторизации')]
class SubscriberAudCommand
{
    #[Execute]
    public function execute(CliIO $io, OauthFeature $feature): void
    {
        $id = $io->readLine('Идентификатор абонента> ');

        $subscriber = HouseSubscriber::findById(rule()->id()->onItem('id', ['id' => $id]));

        if (!$subscriber) {
            $io->writeLine('Не удалось найти абонемента');
        }

        $aud_jti = $feature->register($subscriber->id);

        if ($subscriber->aud_jti != $aud_jti) {
            $subscriber->aud_jti = $aud_jti;

            $subscriber->update();

            $io->writeLine('Данные успешно обновлены');
        } else {
            $io->writeLine('Обновление данных не требуется');
        }
    }
}