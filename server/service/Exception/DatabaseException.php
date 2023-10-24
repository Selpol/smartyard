<?php declare(strict_types=1);

namespace Selpol\Service\Exception;

use RuntimeException;
use Throwable;

class DatabaseException extends RuntimeException
{
    public const UNIQUE_VIOLATION = 1 << 0;

    private int $flag;

    public function __construct(int $flag, string $message = "", int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->flag = $flag;
    }

    public function isUniqueViolation(): bool
    {
        return ($this->flag & self::UNIQUE_VIOLATION) === self::UNIQUE_VIOLATION;
    }
}