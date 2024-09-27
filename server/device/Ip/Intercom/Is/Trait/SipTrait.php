<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is\Trait;

use Selpol\Device\Ip\Intercom\Setting\Sip\Sip;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipOption;
use Throwable;

trait SipTrait
{
    public function getSipStatus(): bool
    {
        try {
            $status = $this->get('/sip/settings');

            return $status['remote']['registerStatus'];
        } catch (Throwable) {
            return false;
        }
    }

    public function getSip(): Sip
    {
        $response = $this->get('/sip/settings');
        $remote = $response['remote'];

        return new Sip($remote['username'] ?? '', $remote['password'] ?? '', $remote['domain'] ?? '', $remote['port'] ?? 0);
    }

    public function getSipOption(): SipOption
    {
        $response = $this->get('/sip/options');

        return new SipOption($response['ringDuration'], $response['talkDuration'], array_values($response['dtmf'] ?? []), $response['echoD']);
    }

    public function setSip(Sip $sip): void
    {
        $this->put('/sip/settings', [
            'videoEnable' => true,
            'remote' => ['username' => $sip->login, 'password' => $sip->password, 'domain' => $sip->server, 'port' => $sip->port],
        ]);
    }

    public function setSipOption(SipOption $sipOption): void
    {
        $this->put('/sip/options', [
            'dtmf' => array_reduce($sipOption->dtmf, static function (array $previous, string|int $current) {
                $previous[$current] = $current;

                return $previous;
            }, []),
            'callDelay' => 0,
            'talkDuration' => $sipOption->talkTimeout,
            'ringDuration' => $sipOption->callTimeout,
            'echoD' => $sipOption->echo
        ]);
    }
}