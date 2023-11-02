<?php declare(strict_types=1);

namespace Selpol\Device\Exception;

use RuntimeException;
use Selpol\Device\Device;
use Selpol\Framework\Kernel\Exception\KernelException;
use Throwable;

class DeviceException extends KernelException
{
    private readonly Device $device;

    public function __construct(Device $device, ?string $localizedMessage = null, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($localizedMessage, $message, $code, $previous);

        $this->device = $device;
    }

    public function getDevice(): Device
    {
        return $this->device;
    }
}