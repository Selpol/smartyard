<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\Is\Trait\ApartmentTrait;
use Selpol\Device\Ip\Intercom\Is\Trait\AudioTrait;
use Selpol\Device\Ip\Intercom\Is\Trait\CmsTrait;
use Selpol\Device\Ip\Intercom\Is\Trait\CodeTrait;
use Selpol\Device\Ip\Intercom\Is\Trait\CommonTrait;
use Selpol\Device\Ip\Intercom\Is\Trait\KeyTrait;
use Selpol\Device\Ip\Intercom\Is\Trait\SipTrait;
use Selpol\Device\Ip\Intercom\Is\Trait\VideoTrait;
use Selpol\Device\Ip\Intercom\Setting\Apartment\ApartmentInterface;
use Selpol\Device\Ip\Intercom\Setting\Audio\AudioInterface;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsInterface;
use Selpol\Device\Ip\Intercom\Setting\Code\CodeInterface;
use Selpol\Device\Ip\Intercom\Setting\Common\CommonInterface;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoInterface;
use Selpol\Device\Ip\Trait\IsTrait;

class IsIntercom extends IntercomDevice implements AudioInterface, VideoInterface, SipInterface, CommonInterface, CmsInterface, ApartmentInterface, KeyInterface, CodeInterface
{
    use IsTrait;
    use AudioTrait;
    use VideoTrait;
    use SipTrait;
    use CommonTrait;
    use CmsTrait;
    use ApartmentTrait;
    use KeyTrait;
    use CodeTrait;

    public function open(int $value): void
    {
        $this->put('/relay/' . ($value + 1) . '/open');
    }

    public function call(int $apartment): void
    {
        $this->get('/sip/test/' . $apartment);
    }

    public function callStop(): void
    {
        $this->put('/v1/call/stop');
    }

    public function reboot(): void
    {
        $this->put('/system/reboot');
    }

    public function reset(): void
    {
        $this->put('/system/factory-reset');
    }
}