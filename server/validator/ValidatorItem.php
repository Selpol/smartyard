<?php

namespace Selpol\Validator;

abstract class ValidatorItem
{
    protected string $message;

    protected function __construct(string $message)
    {
        $this->message = $message;
    }

    protected function getMessage(string $key): string
    {
        return sprintf($this->message, $key);
    }

    /**
     * @param string $key
     * @return ValidatorException
     */
    protected function toException(string $key): ValidatorException
    {
        $message = $this->getMessage($key);

        return new ValidatorException(new ValidatorMessage($message, $key), $message);
    }

    /**
     * @throws ValidatorException
     */
    protected function filter(string $key, array $value, int $filter, array|int $options = null): mixed
    {
        if (!array_key_exists($key, $value) || is_null($value[$key]))
            return null;

        if ($options) {
            if (is_array($options)) {
                $options['flags'] = ($options['flags'] ?? 0) | FILTER_NULL_ON_FAILURE;

                $result = filter_var($value[$key], $filter, $options);
            } else $result = filter_var($value[$key], $filter, $options | FILTER_NULL_ON_FAILURE);
        } else $result = filter_var($value[$key], $filter, FILTER_NULL_ON_FAILURE);

        if (is_null($result))
            throw $this->toException($key);

        return $result;
    }

    /**
     * @param string|int $key
     * @param array $value
     * @return mixed
     * @throws ValidatorException
     */
    public abstract function onItem(string|int $key, array $value): mixed;

    /**
     * @param ValidatorItem[] $items
     * @param string $message
     * @return static
     */
    public static function group(array $items, string $message = 'Групповая валидация'): ValidatorItem
    {
        return new class($items, $message) extends ValidatorItem {
            /**
             * @var ValidatorItem[] $items
             */
            private array $items;

            public function __construct(array $items, string $message)
            {
                parent::__construct($message);

                $this->items = $items;
            }

            public function onItem(string|int $key, array $value): mixed
            {
                foreach ($this->items as $item)
                    $value[$key] = $item->onItem($key, $value);

                return $value[$key];
            }
        };
    }
}