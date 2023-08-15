<?php

namespace logger;

class GroupLogger extends Logger
{
    /** @var Logger[] $loggers */
    private array $loggers;

    /**
     * @param Logger[] $loggers
     */
    public function __construct(array $loggers)
    {
        $this->loggers = $loggers;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        foreach ($this->loggers as $logger)
            $logger->log($level, $message, $context);
    }
}