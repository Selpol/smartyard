<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward;

use Selpol\Device\Ip\Intercom\Beward\Trait\AudioTrait;
use Selpol\Device\Ip\Intercom\Beward\Trait\CommonTrait;
use Selpol\Device\Ip\Intercom\Beward\Trait\SipTrait;
use Selpol\Device\Ip\Intercom\Beward\Trait\VideoTrait;
use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Device\Ip\Intercom\Setting\Audio\AudioInterface;
use Selpol\Device\Ip\Intercom\Setting\Common\CommonInterface;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Device\Ip\Intercom\Setting\Video\VideoInterface;
use Selpol\Device\Ip\Trait\BewardTrait;
use Selpol\Framework\Http\Uri;
use SensitiveParameter;

class DsIntercom extends IntercomDevice implements AudioInterface, VideoInterface, SipInterface, CommonInterface
{
    use BewardTrait, AudioTrait, VideoTrait, SipTrait, CommonTrait;

    public string $login = 'admin';

    public function __construct(Uri $uri, #[SensitiveParameter] string $password, IntercomModel $model)
    {
        parent::__construct($uri, $password, $model);

        $this->clientOption->digest($this->login, $this->password);
    }

    public function open(int $value): void
    {
        $this->get('/cgi-bin/alarmout_cgi', ['action' => 'set', 'Output' => $value, 'Status' => 1]);
    }
}