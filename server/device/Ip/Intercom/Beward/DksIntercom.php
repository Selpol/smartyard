<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward;

use Selpol\Device\Ip\Intercom\Beward\Trait\ApartmentTrait;
use Selpol\Device\Ip\Intercom\Beward\Trait\AudioTrait;
use Selpol\Device\Ip\Intercom\Beward\Trait\CmsTrait;
use Selpol\Device\Ip\Intercom\Beward\Trait\CodeTrait;
use Selpol\Device\Ip\Intercom\Beward\Trait\CommonTrait;
use Selpol\Device\Ip\Intercom\Beward\Trait\KeyTrait;
use Selpol\Device\Ip\Intercom\Beward\Trait\SipTrait;
use Selpol\Device\Ip\Intercom\Beward\Trait\VideoTrait;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Device\Ip\Intercom\Setting\Apartment\ApartmentInterface;
use Selpol\Device\Ip\Intercom\Setting\Audio\AudioInterface;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsInterface;
use Selpol\Device\Ip\Intercom\Setting\Code\CodeInterface;
use Selpol\Device\Ip\Intercom\Setting\Common\CommonInterface;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoInterface;
use Selpol\Device\Ip\Trait\BewardTrait;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

class DksIntercom extends IntercomDevice implements AudioInterface, VideoInterface, SipInterface, CommonInterface, CmsInterface, ApartmentInterface, KeyInterface, CodeInterface
{
    use BewardTrait;
    use AudioTrait;
    use VideoTrait;
    use SipTrait;
    use CommonTrait;
    use CmsTrait;
    use ApartmentTrait;
    use KeyTrait;
    use CodeTrait;

    public string $login = 'admin';

    public function __construct(Uri $uri, #[SensitiveParameter] string $password, IntercomModel $model, ?int $id = null)
    {
        parent::__construct($uri, $password, $model, $id);

        $this->clientOption->digest($this->login, $this->password);
    }

    public function open(int $value): void
    {
        switch ($value) {
            case 0:
                $this->get('/cgi-bin/intercom_cgi', ['action' => 'maindoor']);

                break;
            case 1:
                $this->get('/cgi-bin/intercom_cgi', ['action' => 'altdoor']);

                break;
            case 2:
                $this->get('/cgi-bin/intercom_cgi', ['action' => 'light', 'Enable' => 'on']);

                sleep(100);

                $this->get('/cgi-bin/intercom_cgi', ['action' => 'light', 'Enable' => 'off']);

                break;
        }
    }
}