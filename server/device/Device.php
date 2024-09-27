<?php declare(strict_types=1);

namespace Selpol\Device;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Selpol\Framework\Client\Client;
use Selpol\Framework\Http\Uri;

abstract class Device implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected readonly Client $client;

    protected function __construct(public readonly Uri $uri)
    {
        $this->client = container(Client::class);

        $this->logger = file_logger('device');
    }
}