<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal\Statement;

use Selpol\Device\Ip\Intercom\IntercomDevice;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Schedule\Internal\Context;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Task\Tasks\Intercom\IntercomConfigureTask;

class IntercomSyncStatement extends Statement
{
    public string|int $intercom;

    public function __construct(string|int $intercom)
    {
        $this->intercom = $intercom;
    }

    public function execute(Context $context): StatementResult
    {
        if (is_string($this->intercom)) {
            /** @var IntercomDevice $intercom */
            $intercom = $context->getOrThrow($this->intercom);

            task(new IntercomConfigureTask($intercom->intercom->house_domophone_id))->high()->async();
        } else {
            task(new IntercomConfigureTask($this->intercom))->high()->async();
        }

        return StatementResult::Success;
    }

    public static function check(array $value): void
    {
        if (!array_key_exists('intercom', $value)) {
            throw new KernelException('Отсуствует идентификатор домофона');
        }

        if (is_int($value['intercom']) && !DeviceIntercom::findById($value['intercom'])) {
            throw new KernelException('Домофон не найден');
        }
    }

    public static function parse(array $value): Statement
    {
        return new IntercomSyncStatement($value['intercom']);
    }
}
