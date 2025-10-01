<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal\Statement;

use Selpol\Feature\Schedule\Internal\Context;
use Selpol\Framework\Kernel\Exception\KernelException;

class IfValueStatement extends Statement
{
    public string $value;

    /**
     * @var Statement[]
     */
    public array $children;

    public function __construct(string $value, array $children)
    {
        $this->value = $value;

        $this->children = $children;
    }

    public function execute(Context $context): StatementResult
    {
        if ($context->time->at($this->value)) {
            foreach ($this->children as $child) {
                $result = $child->execute($context);

                if ($result != StatementResult::Success) {
                    return $result;
                }
            }
        }

        return StatementResult::Success;
    }

    public static function check(array $value): void
    {
        if (!array_key_exists('value', $value)) {
            throw new KernelException('Не передано значение времени');
        }

        if (!array_key_exists('children', $value)) {
            throw new KernelException('Не переданы дочерние действия');
        }

        if (!is_array($value['children'])) {
            throw new KernelException('Не верный тип дочерних действий');
        }

        foreach ($value['children'] as $child) {
            parent::check($child);
        }
    }

    public static function parse(array $value): Statement
    {
        $children = [];

        foreach ($value['children'] as $child) {
            $children[] = parent::parse($child);
        }

        return new IfValueStatement($value['value'], $children);
    }
}
