<?php

namespace Selpol\Device;

use RuntimeException;
use Throwable;

class DeviceException extends RuntimeException
{
    private readonly Device $device;

    public function __construct(Device $device, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->device = $device;
    }

    public function getDevice(): Device
    {
        return $this->device;
    }
}