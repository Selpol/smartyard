<?php declare(strict_types=1);

namespace Selpol\Device;

use Selpol\Device\Ip\IpDevice;
use Selpol\Http\Uri;
use Selpol\Service\ClientService;

abstract class Device
{
    public readonly Uri $uri;

    protected ClientService $client;

    protected function __construct(Uri $uri)
    {
        $this->uri = $uri;

        $this->client = container(ClientService::class);
    }

    public function asIp(): ?IpDevice
    {
        if ($this instanceof IpDevice)
            return $this;

        return null;
    }
}