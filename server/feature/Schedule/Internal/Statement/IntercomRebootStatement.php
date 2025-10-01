<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal\Statement;

use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Schedule\Internal\Context;
use Selpol\Framework\Kernel\Exception\KernelException;

class IntercomRebootStatement extends Statement
{
    public string|int $intercom;

    public function __construct(string|int $intercom)
    {
        $this->intercom = $intercom;
    }

    public function execute(Context $context): bool
    {
        $intercom = is_string($this->intercom) ? $context->getOrThrow($this->intercom) : intercom($this->intercom);
        $intercom->reboot();

        return true;
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
        return new IntercomRebootStatement($value['intercom']);
    }
}
