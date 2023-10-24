<?php declare(strict_types=1);

namespace Selpol\Device;

use Selpol\Device\Ip\IpDevice;
use Selpol\Framework\Client\Client;
use Selpol\Framework\Http\Uri;

abstract class Device
{
    public readonly Uri $uri;

    protected readonly Client $client;

    protected function __construct(Uri $uri)
    {
        $this->uri = $uri;

        $this->client = container(Client::class);
    }

    public function asIp(): ?IpDevice
    {
        if ($this instanceof IpDevice)
            return $this;

        return null;
    }
}