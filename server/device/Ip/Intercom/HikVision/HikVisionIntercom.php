<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\HikVision;

use Selpol\Device\Ip\Intercom\HikVision\Trait\ApartmentTrait;
use Selpol\Device\Ip\Intercom\HikVision\Trait\AudioTrait;
use Selpol\Device\Ip\Intercom\HikVision\Trait\CommonTrait;
use Selpol\Device\Ip\Intercom\HikVision\Trait\KeyTrait;
use Selpol\Device\Ip\Intercom\HikVision\Trait\SipTrait;
use Selpol\Device\Ip\Intercom\HikVision\Trait\VideoTrait;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Device\Ip\Intercom\Setting\Apartment\ApartmentInterface;
use Selpol\Device\Ip\Intercom\Setting\Audio\AudioInterface;
use Selpol\Device\Ip\Intercom\Setting\Common\CommonInterface;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyHandlerInterface;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoInterface;
use Selpol\Device\Ip\Trait\HikVisionTrait;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

class HikVisionIntercom extends IntercomDevice implements AudioInterface, VideoInterface, SipInterface, CommonInterface, ApartmentInterface, KeyInterface, KeyHandlerInterface
{
    use HikVisionTrait;
    use AudioTrait;
    use VideoTrait;
    use SipTrait;
    use CommonTrait;
    use ApartmentTrait;
    use KeyTrait;

    public string $login = 'admin';

    public function __construct(Uri $uri, #[SensitiveParameter] string $password, IntercomModel $model)
    {
        parent::__construct($uri, $password, $model);

        $this->clientOption->anySafe($this->login, $this->password);
    }

    public function open(int $value): void
    {
        $this->put('/ISAPI/AccessControl/RemoteControl/door/' . ($value + 1), '<cmd>open</cmd>', ['Content-Type' => 'application/xml']);
    }

    public function reboot(): void
    {
        $this->put('/ISAPI/System/reboot');
    }

    public function reset(): void
    {
        $this->put('/ISAPI/System/factoryReset', ['mode' => 'basic']);
    }
}