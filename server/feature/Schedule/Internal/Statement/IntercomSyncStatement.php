<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal\Statement;

use Selpol\Device\Ip\Intercom\IntercomDevice;
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

    public function execute(Context $context): void
    {
        if (is_string($this->intercom)) {
            /** @var IntercomDevice $intercom */
            $intercom = $context->getOrThrow($this->intercom);

            task(new IntercomConfigureTask($intercom->intercom->house_domophone_id))->high()->dispatch();
        } else {
            task(new IntercomConfigureTask($this->intercom))->high()->dispatch();
        }
    }

    public static function check(array $value): void
    {
        if (!array_key_exists('intercom', $value)) {
            throw new KernelException('Отсуствует идентификатор домофона');
        }
    }

    public static function parse(array $value): Statement
    {
        return new IntercomSyncStatement($value['intercom']);
    }
}
