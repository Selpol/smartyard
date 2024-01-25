<?php declare(strict_types=1);

namespace Selpol\Device\Ip\Intercom\Is;

class Is5Intercom extends IsIntercom
{
    public function setSyslog(string $server, int $port): static
    {
        $this->put('/v1/network/syslog', ['addr' => $server, 'port' => $port]);

        return $this;
    }

    public function setDDns(bool $value, array $options = []): static
    {
        if (!$value)
            $this->put('/v1/ddns', ['enabled' => false]);

        return $this;
    }

    public function setDisplayText(string $title): static
    {
        if ($title === "") $this->put('/panelDisplay/settings', ['strDisplay' => false]);
        else $this->put('/panelDisplay/settings', ['strDisplay' => true, 'speed' => 500, 'imgStr' => $title]);

        return $this;
    }
}