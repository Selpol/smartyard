<?php declare(strict_types=1);

namespace Selpol\Device;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Selpol\Framework\Client\Client;
use Selpol\Framework\Http\Uri;

abstract class Device implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public readonly Uri $uri;

    protected readonly Client $client;

    protected function __construct(Uri $uri)
    {
        $this->uri = $uri;

        $this->client = container(Client::class);

        $this->logger = file_logger('device');
    }
}