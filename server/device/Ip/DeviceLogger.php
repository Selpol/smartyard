<?php declare(strict_types=1);

namespace Selpol\Device\Ip;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class DeviceLogger implements LoggerInterface
{
    use LoggerTrait;

    public function __construct(private readonly string $file)
    {
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        file_put_contents(
            $this->file,
            '[' . date('Y-m-d H:i:s') . '] ' . $level . ': ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND
        );
    }
}