<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal\Statement;

use Selpol\Feature\Schedule\Internal\Context;

class TopStatement extends Statement
{
    /**
     * @var Statement[]
     */
    public array $children;

    public function __construct(array $children)
    {
        $this->children = $children;
    }

    public function execute(Context $context): StatementResult
    {
        foreach ($this->children as $child) {
            $result = $child->execute($context);

            if ($result != StatementResult::Success) {
                return $result;
            }
        }

        return StatementResult::Success;
    }

    public static function check(array $value): void
    {
        foreach ($value as $child) {
            parent::check($child);
        }
    }

    public static function parse(array $value): Statement
    {
        $children = [];

        foreach ($value as $child) {
            $children[] = parent::parse($child);
        }

        return new TopStatement($children);
    }
}
