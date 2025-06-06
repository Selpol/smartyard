<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal\Statement;

use Selpol\Feature\Schedule\Internal\Context;
use Selpol\Framework\Kernel\Exception\KernelException;

class IntercomOpenStatement extends Statement
{
    public string|int $intercom;

    public function __construct(string|int $intercom)
    {
        $this->intercom = $intercom;
    }

    public function execute(Context $context): void
    {
        $intercom = is_string($this->intercom) ? $context->getOrThrow($this->intercom) : intercom($this->intercom);
        $intercom->open(0);
    }

    public static function check(array $value): void
    {
        if (!array_key_exists('intercom', $value)) {
            throw new KernelException('Отсуствует идентификатор домофона');
        }
    }

    public static function parse(array $value): Statement
    {
        return new IntercomOpenStatement($value['intercom']);
    }
}
