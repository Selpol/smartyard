<?php

namespace Selpol\Validator;

abstract class Rule extends ValidatorItem
{
    public static function required(string $message = 'Поле %s обязательно для заполнения'): static
    {
        return new class($message) extends Rule {
            protected function __construct(string $message)
            {
                parent::__construct($message);
            }

            public function onItem(string $key, array $value): mixed
            {
                if (!array_key_exists($key, $value))
                    throw $this->toException($key);

                return $value[$key];
            }
        };
    }

    public static function nonNullable(string $message = 'Поле %s не может быть пустым'): static
    {
        return new class($message) extends Rule {
            public function onItem(string $key, array $value): mixed
            {
                if (is_null($value[$key]))
                    throw $this->toException($key);

                return $value[$key];
            }
        };
    }

    public static function bool(string $message = 'Поле %s должно быть булевым значением'): static
    {
        return new class($message) extends Rule {
            public function onItem(string $key, array $value): mixed
            {
                return $this->filter($key, $value, FILTER_VALIDATE_BOOL);
            }
        };
    }

    public static function int(string $message = 'Поле %s должно быть челочисленным значением'): static
    {
        return new class($message) extends Rule {
            public function onItem(string $key, array $value): mixed
            {
                return $this->filter($key, $value, FILTER_VALIDATE_INT);
            }
        };
    }

    public static function float(string $message = 'Поле %s должно быть числом с плавающей точкой'): static
    {
        return new class($message) extends Rule {
            public function onItem(string $key, array $value): mixed
            {
                return $this->filter($key, $value, FILTER_VALIDATE_FLOAT);
            }
        };
    }

    public static function min(int|float $min = -2147483647, string $message = 'Поле %s меньше %d'): static
    {
        return new class($min, $message) extends Rule {
            private int|float $min;

            public function __construct(int|float $min, string $message)
            {
                parent::__construct($message);

                $this->min = $min;
            }

            protected function getMessage(string $key): string
            {
                return sprintf($this->message, $key, $this->min);
            }

            public function onItem(string $key, array $value): mixed
            {
                if (is_int($this->min))
                    return $this->filter($key, $value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $this->min]]);
                else if (is_float($this->min))
                    return $this->filter($key, $value, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => $this->min]]);

                return null;
            }
        };
    }

    public static function max(int|float $max = 2147483647, string $message = 'Поле %s больше %d'): static
    {
        return new class($max, $message) extends Rule {
            private int|float $max;

            public function __construct(int|float $max, string $message)
            {
                parent::__construct($message);

                $this->max = $max;
            }

            protected function getMessage(string $key): string
            {
                return sprintf($this->message, $key, $this->max);
            }

            public function onItem(string $key, array $value): mixed
            {
                if (is_int($this->max))
                    return $this->filter($key, $value, FILTER_VALIDATE_INT, ['options' => ['max_range' => $this->max]]);
                else if (is_float($this->max))
                    return $this->filter($key, $value, FILTER_VALIDATE_FLOAT, ['options' => ['max_range' => $this->max]]);

                return null;
            }
        };
    }

    public static function length(int $min = 0, int $max = 1024, string $message = 'Длина %s не может выходить из диапазона %d-%d'): static
    {
        return new class($min, $max, $message) extends Rule {
            private int $min;
            private int $max;

            public function __construct(int $min, int $max, string $message)
            {
                parent::__construct($message);

                $this->min = $min;
                $this->max = $max;
            }

            protected function getMessage(string $key): string
            {
                return sprintf($this->message, $key, $this->min, $this->max);
            }

            public function onItem(string $key, array $value): mixed
            {
                if (!array_key_exists($key, $value))
                    return null;

                if (!is_string($value[$key]) || strlen($value[$key]) < $this->min || strlen($value[$key]) > $this->max)
                    throw new ValidatorException(new ValidatorMessage($this->getMessage($key)));

                return $value[$key];
            }
        };
    }

    public static function regexp(string $value, string $message = 'Поле %s должно быть определенного формата'): static
    {
        return new class($value, $message) extends Rule {
            private string $value;

            public function __construct(string $value, string $message)
            {
                parent::__construct($message);

                $this->value = $value;
            }

            public function onItem(string $key, array $value): mixed
            {
                return $this->filter($key, $value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => $this->value]]);
            }
        };
    }

    public static function url(string $message = 'Поле %s должно быть формата ссылки', bool $path = false, bool $query = false): static
    {
        return new class($path, $query, $message) extends Rule {
            private bool $path;
            private bool $query;

            public function __construct(bool $path, bool $query, string $message)
            {
                parent::__construct($message);

                $this->path = $path;
                $this->query = $query;
            }

            public function onItem(string $key, array $value): mixed
            {
                if ($this->path || $this->query)
                    return $this->filter($key, $value, FILTER_VALIDATE_URL, ($this->path ? FILTER_FLAG_PATH_REQUIRED : 0) | ($this->query ? FILTER_FLAG_QUERY_REQUIRED : 0));

                return $this->filter($key, $value, FILTER_VALIDATE_URL);
            }
        };
    }

    public static function email(string $message = 'Поле %s должно быть формата почты'): static
    {
        return new class($message) extends Rule {
            public function onItem(string $key, array $value): mixed
            {
                return $this->filter($key, $value, FILTER_VALIDATE_EMAIL);
            }
        };
    }

    public static function ipV4(string $message = 'Поле %s должно быть формата ipV4'): static
    {
        return new class($message) extends Rule {
            public function onItem(string $key, array $value): mixed
            {
                return $this->filter($key, $value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            }
        };
    }

    public static function ipV6(string $message = 'Поле %s должно быть формата ipV6'): static
    {
        return new class($message) extends Rule {
            public function onItem(string $key, array $value): mixed
            {
                return $this->filter($key, $value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
            }
        };
    }

    public static function mac(string $message = 'Поле %s должно быть формата MAC-адреса'): static
    {
        return new class($message) extends Rule {
            public function onItem(string $key, array $value): mixed
            {
                return $this->filter($key, $value, FILTER_VALIDATE_MAC);
            }
        };
    }

    public static function in(array $value, string $message = 'Поле %s находится в не допустимого диапазона'): static
    {
        return new class($value, $message) extends Rule {
            private array $value;

            public function __construct(array $value, string $message)
            {
                parent::__construct($message);

                $this->value = $value;
            }

            public function onItem(string $key, array $value): mixed
            {
                if (!array_key_exists($key, $value))
                    return null;

                if (!in_array($value[$key], $this->value))
                    throw $this->toException($key);

                return $value[$key];
            }
        };
    }

    public static function uuid(string $message = 'Поле %s должно быть формата UUID'): static
    {
        return static::regexp('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $message);
    }

    public static function id(): static
    {
        return static::group([Rule::required(), Rule::int(), Rule::min(0), Rule::max(), Rule::nonNullable()]);
    }

    public static function custom(callable $value, string $message = 'Поле %s не прошло проверку'): static
    {
        return new class($value, $message) extends Rule {
            /** @var callable $value */
            private $value;

            public function __construct(callable $value, string $message)
            {
                parent::__construct($message);

                $this->value = $value;
            }

            public function onItem(string $key, array $value): mixed
            {
                return call_user_func($this->value, [$key, $value]);
            }
        };
    }
}