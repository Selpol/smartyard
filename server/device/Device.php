<?php

namespace Selpol\Device;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Http\Uri;
use Selpol\Service\ClientService;

/**
 * @author https://github.com/rosteleset/SmartYard-Server/blob/feature/smart_autoconfig
 */
abstract class Device
{
    public readonly Uri $uri;

    protected function __construct(Uri $uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return void
     * @throws DeviceException
     */
    public function prepare(): void
    {
        throw new DeviceException($this);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    protected function client(): ClientService
    {
        return container(ClientService::class);
    }
}