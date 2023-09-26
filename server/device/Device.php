<?php declare(strict_types=1);

namespace Selpol\Device;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Device\Ip\IpDevice;
use Selpol\Http\Uri;
use Selpol\Service\ClientService;

abstract class Device
{
    public readonly Uri $uri;

    protected function __construct(Uri $uri)
    {
        $this->uri = $uri;
    }

    public function asIp(): ?IpDevice
    {
        if ($this instanceof IpDevice)
            return $this;

        return null;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    protected function client(): ClientService
    {
        return container(ClientService::class);
    }
}