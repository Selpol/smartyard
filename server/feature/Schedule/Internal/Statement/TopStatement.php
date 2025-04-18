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

    public function execute(Context $context): void
    {
        foreach ($this->children as $child) {
            $child->execute($context);
        }
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
