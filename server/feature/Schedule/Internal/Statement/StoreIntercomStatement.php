<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal\Statement;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Schedule\Internal\Context;
use Selpol\Framework\Kernel\Exception\KernelException;

class StoreIntercomStatement extends Statement
{
    public int $intercom;

    public function __construct(int $intercom)
    {
        $this->intercom = $intercom;
    }

    public function execute(Context $context): StatementResult
    {
        $intercom = intercom($this->intercom);

        if (!$intercom) {
            return StatementResult::Critical;
        }

        $context->set('intercom', $intercom);

        return StatementResult::Success;
    }

    public static function check(array $value): void
    {
        if (!array_key_exists('intercom', $value)) {
            throw new KernelException('Отсуствует идентификатор домофона');
        }

        if (!DeviceIntercom::findById($value['intercom'])) {
            throw new KernelException('Домофон не найден');
        }
    }

    public static function parse(array $value): Statement
    {
        return new StoreIntercomStatement($value['intercom']);
    }
}
