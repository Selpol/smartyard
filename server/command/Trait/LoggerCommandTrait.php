<?php declare(strict_types=1);

namespace Selpol\Command\Trait;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

trait LoggerCommandTrait
{
    use LoggerAwareTrait;

    /**
     * @return LoggerInterface|null
     */
    protected function getLogger(): ?LoggerInterface
    {
        if ($this->logger === null)
            $this->logger = stack_logger([file_logger('cli'), echo_logger()]);

        return $this->logger;
    }
}