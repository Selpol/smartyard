<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom;

use Selpol\Device\Ip\IpDevice;
use Selpol\Entity\Model\Core\CoreVar;
use Selpol\Entity\Repository\Core\CoreVarRepository;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

abstract class IntercomDevice extends IpDevice
{
    public IntercomModel $model;

    public function __construct(Uri $uri, #[SensitiveParameter] string $password, IntercomModel $model)
    {
        parent::__construct($uri, $password);

        $this->model = $model;

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

    public function getIntercomClean(): array
    {
        $coreVar = container(CoreVarRepository::class)->findByName('intercom.clean');

        $value = $coreVar->var_value ? json_decode($coreVar->var_value, true) : [];

        if (!is_array($value))
            $value = [];

        return [
            'unlockTime' => $value['unlockTime'] ?? 5,

            'callTimeout' => $value['callTimeout'] ?? 45,
            'talkTimeout' => $value['talkTimeout'] ?? 90,

            'sos' => $value['sos'] ?? 'SOS',
            'concierge' => $value['concierge'] ?? 9999
        ];
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