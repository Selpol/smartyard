<?php declare(strict_types=1);

namespace Selpol\Command\Device;

use Selpol\Command\Trait\LoggerCommandTrait;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('device:reboot', 'Перезапустить устройство')]
class RebootCommand
{
    use LoggerCommandTrait;

    #[Execute]
    public function execute(int $id): void
    {
        if ($device = intercom($id)) {
            $device->reboot();
            $this->getLogger()->debug('Домофон перезапущен', ['id' => $id]);
        } else $this->getLogger()->debug('Домофон не найден', ['id' => $id]);
    }
}