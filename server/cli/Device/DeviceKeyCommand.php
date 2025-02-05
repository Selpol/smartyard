<?php declare(strict_types=1);

namespace Selpol\Cli\Device;

use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;

#[Executable('device:key', 'Выгрузить ключи с устройства')]
class DeviceKeyCommand
{
    #[Execute]
    public function execute(int $value): void
    {
        $intercom = intercom($value);

        if ($intercom instanceof KeyInterface) {
            $keys = $intercom->getKeys(null);

            $result = 'Квартира;Ключ';

            foreach ($keys as $key) {
                $result .= PHP_EOL . $key->apartment . ';' . $key->key;
            }

            file_put_contents(path('private/key.csv'), $result);
        }
    }
}