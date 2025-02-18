<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Beward\Trait;

use Selpol\Device\Ip\Intercom\Setting\Sip\Sip;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipOption;

trait SipTrait
{
    public function getSipStatus(): bool
    {
        $response = $this->get('/cgi-bin/sip_cgi', ['action' => 'regstatus'], parse: ['type' => 'param']);

        return array_key_exists('AccountReg1', $response) && $response['AccountReg1'] == true || array_key_exists('AccountReg2', $response) && $response['AccountReg2'] == true;
    }

    public function getSip(): Sip
    {
        $response = $this->get('/cgi-bin/sip_cgi', ['action' => 'get'], parse: ['type' => 'param']);

        return new Sip($response['AccUser1'], $response['AccPassword1'], $response['RegServerUrl1'], intval($response['RegServerPort1']));
    }

    public function getSipOption(): SipOption
    {
        $response = $this->getIntercomCgi();
        $sip = $this->get('/cgi-bin/sip_cgi', ['action' => 'get'], parse: ['type' => 'param']);
        $audio = $this->get('/cgi-bin/audio_cgi', ['action' => 'get'], parse: ['type' => 'param']);

        return new SipOption(intval($response['CallTimeout']), intval($response['TalkTimeout']), [$sip['DtmfSignal1'], $sip['DtmfSignal2'], $sip['DtmfSignal3']], $audio['EchoCancellation'] === 'open');
    }

    public function setSipStatus(bool $value): void
    {
        $this->get('/webs/SIP1CfgEx', ['cksip' => $value ? 1 : 0, 'ckenablesip' => $value ? 1 : 0]);
    }

    public function setSip(Sip $sip): void
    {
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
            'streamtype' => $this->resolver->int('sip.stream', 0),
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