<?php

namespace Selpol\Validator;

use Stringable;

class ValidatorMessage implements Stringable
{
    private string $key;
    private string $message;

    public function __construct(string $key, string $message)
    {
        $this->key = $key;
        $this->message = $message;
    }

    public function getKey(): string
    {
        return $this->key;
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