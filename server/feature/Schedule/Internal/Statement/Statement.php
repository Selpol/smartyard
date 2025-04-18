<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal\Statement;

use Selpol\Feature\Schedule\Internal\Context;
use Selpol\Framework\Kernel\Exception\KernelException;

abstract class Statement
{
    /**
     * @var array<string, Statement>
     */
    public static array $STATEMENTS = [
        'top' => TopStatement::class,

        'store_intercom' => StoreIntercomStatement::class,

        'if_value' => IfValueStatement::class,

        'intercom_sync' => IntercomSyncStatement::class,
        'intercom_relay' => IntercomRelayStatement::class,

        'intercom_open' => IntercomOpenStatement::class,
        'intercom_reboot' => IntercomRebootStatement::class,
        'intercom_reset' => IntercomResetStatement::class,
    ];

    public abstract function execute(Context $context): void;

    /**
     * @param array $value
     * @return void
     * @throws KernelException
     */
    public static function check(array $value): void
    {
        self::$STATEMENTS[$value['type']]::check($value);
    }

    public static function parse(array $value): Statement
    {
        return self::$STATEMENTS[$value['type']]::parse($value);
    }
}
