<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\HikVision\Trait;

use Selpol\Device\Ip\Intercom\Setting\Sip\Sip;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipOption;
use Throwable;

trait SipTrait
{
    public function getSipStatus(): bool
    {
        try {
            $response = $this->get('/ISAPI/System/Network/SIP/1');

            if (!$response) {
                return false;
            }

            return collection_get($response, 'Standard.registerStatus', false) == true;
        } catch (Throwable) {
            return false;
        }
    }

    public function getSip(): Sip
    {
        $response = $this->get('/ISAPI/System/Network/SIP');
        $standard = $response['SIPServer']['Standard'];

        if ($standard['enabled']) {
            return new Sip($standard['authID'], $standard['password'], $standard['proxy'], intval($standard['port']));
        }

        return new Sip('', '', '', 0);
    }

    public function getSipOption(): SipOption
    {
        $response = $this->get('/ISAPI/VideoIntercom/operationTime');

        return new  SipOption(intval($response['OperationTime']['maxRingTime']), intval($response['OperationTime']['talkTime']), [], false);
    }

    public function setSip(Sip $sip): void
    {
        $this->put('/ISAPI/System/Network/SIP', "<SIPServerList><SIPServer><id>1</id><Standard><enabled>true</enabled><proxy>" . $sip->server . "</proxy><proxyPort>" . $sip->port . "</proxyPort><displayName>" . $sip->login . "</displayName><userName>" . $sip->login . "</userName><authID>" . $sip->login . "</authID><password>" . $sip->password . "</password><expires>30</expires></Standard></SIPServer></SIPServerList>", ['Content-Type' => 'application/xml']);
    }

    public function setSipOption(SipOption $sipOption): void
    {
        $this->put('/ISAPI/VideoIntercom/operationTime', '<OperationTime><maxRingTime>' . $sipOption->callTimeout . '</maxRingTime><talkTime>' . $sipOption->talkTimeout . '</talkTime></OperationTime>', ['Content-Type' => 'application/xml']);
    }
}