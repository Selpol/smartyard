<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Device\Ip\Intercom\Setting\IntercomClean;
use Selpol\Device\Ip\IpDevice;
use Selpol\Entity\Model\Core\CoreVar;
use Selpol\Entity\Repository\Core\CoreVarRepository;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

abstract class IntercomDevice extends IpDevice
{
    public function __construct(Uri $uri, #[SensitiveParameter] string $password, public IntercomModel $model, ?int $id = null)
    {
        parent::__construct($uri, $password, $id);

        match ($this->model->option->auth) {
            IntercomAuth::ANY_SAFE => $this->clientOption->anySafe($this->login, $password),
            IntercomAuth::BASIC => $this->clientOption->basic($this->login, $password),
            IntercomAuth::DIGEST => $this->clientOption->digest($this->login, $password),
        };

        $this->setLogger(file_logger('intercom'));
    }

    public function open(int $value): void
    {
    }

    public function call(int $apartment): void
    {
    }

    public function callStop(): void
    {
    }

    public function reboot(): void
    {
    }

    public function reset(): void
    {
    }

    public function getIntercomClean(): IntercomClean
    {
        $coreVar = container(CoreVarRepository::class)->findByName('intercom.clean');

        $value = $coreVar->var_value ? json_decode($coreVar->var_value, true) : [];

        if (!is_array($value)) {
            $value = [
                'unlockTime' => 5,

                'callTimeout' => 45,
                'talkTimeout' => 90,

                'sos' => 'SOS',
                'concierge' => '9999'
            ];
        }

        return new IntercomClean(
            array_key_exists('unlockTime', $value) ? $value['unlockTime'] : 5,

            array_key_exists('callTimeout', $value) ? $value['callTimeout'] : 45,
            array_key_exists('talkTimeout', $value) ? $value['talkTimeout'] : 90,

            array_key_exists('sos', $value) ? strval($value['sos']) : 'SOS',
            array_key_exists('concierge', $value) ? strval($value['concierge']) : '9999'
        );
    }

    public function getIntercomNtp(): array
    {
        $coreVar = CoreVar::getRepository()->findByName('intercom.ntp');
        $servers = json_decode($coreVar->var_value, true);

        $server = $servers[array_rand($servers)];
        $ntp = uri($server);

        return [$ntp->getHost(), $ntp->getPort() ?? 123];
    }
}