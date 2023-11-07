<?php declare(strict_types=1);

namespace Selpol\Device;

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
}