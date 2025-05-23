<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal\Statement;

use Selpol\Feature\Schedule\Internal\Context;
use Selpol\Framework\Kernel\Exception\KernelException;

class StoreIntercomStatement extends Statement
{
    public int $intercom;

    public function __construct(int $intercom)
    {
        $this->intercom = $intercom;
    }

    public function execute(Context $context): void
    {
        $context->set('intercom', intercom($this->intercom));
    }

    public static function check(array $value): void
    {
        if (!array_key_exists('intercom', $value)) {
            throw new KernelException('Отсуствует идентификатор домофона');
        }
    }

    public static function parse(array $value): Statement
    {
        return new StoreIntercomStatement($value['intercom']);
    }
}
