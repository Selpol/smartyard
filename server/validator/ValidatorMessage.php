<?php declare(strict_types=1);

namespace Selpol\Validator;

use Stringable;

class ValidatorMessage implements Stringable
{
    private string|int $key;
    private array $value;

    private string $message;

    public function __construct(string|int $key, array $value, string $message)
    {
        $this->key = $key;
        $this->value = $value;

        $this->message = $message;
    }

    public function getKey(): string|int
    {
        return $this->key;
    }

    public function getValue(): array
    {
        return $this->value;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function __toString(): string
    {
        return $this->getMessage();
    }
}