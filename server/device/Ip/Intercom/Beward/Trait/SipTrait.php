<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward\Trait;

use Selpol\Device\Ip\Intercom\Setting\Sip\Sip;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipOption;

trait SipTrait
{
    public function getSipStatus(): bool
    {
        $response = $this->parseParamValueHelp($this->get('/cgi-bin/sip_cgi', ['action' => 'regstatus'], parse: false));

        return array_key_exists('AccountReg1', $response) && $response['AccountReg1'] == true || array_key_exists('AccountReg2', $response) && $response['AccountReg2'] == true;
    }

    public function getSip(): Sip
    {
        $response = $this->parseParamValueHelp($this->get('/cgi-bin/sip_cgi', ['action' => 'get'], parse: false));

        return new Sip($response['AccUser1'], $response['AccPassword1'], $response['RegServerUrl1'], intval($response['RegServerPort1']));
    }

    public function getSipOption(): SipOption
    {
        $response = $this->getIntercomCgi();
        $sip = $this->parseParamValueHelp($this->get('/cgi-bin/sip_cgi', ['action' => 'get'], parse: false));
        $audio = $this->parseParamValueHelp($this->get('/cgi-bin/audio_cgi', ['action' => 'get'], parse: false));

        return new SipOption(intval($response['CallTimeout']), intval($response['TalkTimeout']), [$sip['DtmfSignal1'], $sip['DtmfSignal2'], $sip['DtmfSignal3']], $audio['EchoCancellation'] === 'open');
    }

    public function setSip(Sip $sip): void
    {
        $info = $this->getSysInfo();

        $stream = trim($info['DeviceModel']) == 'DKS977957_rev5.5.3.9.2' ? 1 : 0;

        $this->get('/webs/SIP1CfgEx', [
            'cksip' => 1,
            'sipname' => $sip->login,
            'number' => $sip->login,
            'username' => $sip->login,
            'pass' => $sip->password,
            'sipport' => $sip->port,
            'ckenablesip' => 1,
            'regserver' => $sip->server,
            'regport' => $sip->port,
            'sipserver' => $sip->server,
            'sipserverport' => $sip->port,
            'streamtype' => $stream,
            'packettype' => 1,
            'dtfmmod' => 0,
            'passchanged' => 1,
            'proxyurl' => '',
            'proxyport' => 5060,
            'ckincall' => 1,
        ]);
    }

    public function setSipOption(SipOption $sipOption): void
    {
        $this->get('/webs/SIPExtCfgEx', ['dtmfout1' => $sipOption->dtmf[0], 'dtmfout2' => $sipOption->dtmf[1], 'dtmfout3' => $sipOption->dtmf[2]]);
        $this->get('/cgi-bin/audio_cgi', ['action' => 'set', 'EchoCancellation' => $sipOption->echo ? 'open' : 'close']);
        $this->get('/cgi-bin/intercom_cgi', ['action' => 'set', 'CallTimeout' => $sipOption->callTimeout, 'TalkTimeout' => $sipOption->talkTimeout]);
    }
}