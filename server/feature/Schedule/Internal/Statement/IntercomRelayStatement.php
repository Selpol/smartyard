<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal\Statement;

use Selpol\Device\Ip\Intercom\Setting\Common\CommonInterface;
use Selpol\Feature\Schedule\Internal\Context;
use Selpol\Framework\Kernel\Exception\KernelException;

class IntercomRelayStatement extends Statement
{
    public string|int $intercom;
    public int $value;

    public function __construct(string|int $intercom, int $value)
    {
        $this->intercom = $intercom;
        $this->value = $value;
    }

    public function execute(Context $context): void
    {
        $intercom = is_string($this->intercom) ? $context->getOrThrow($this->intercom) : intercom($this->intercom);

        if ($intercom instanceof CommonInterface) {
            $relay = $intercom->getRelay(0);
            $relay->lock = (bool)$this->value;

            $intercom->setRelay($relay, 0);
        }
    }

    public static function check(array $value): void
    {
        if (!array_key_exists('intercom', $value)) {
            throw new KernelException('Отсуствует идентификатор домофона');
        }

        if (!array_key_exists('value', $value)) {
            throw new KernelException('Отсуствует статус входа');
        }
    }

    public static function parse(array $value): Statement
    {
        return new IntercomRelayStatement($value['intercom'], $value['value']);
    }
}
