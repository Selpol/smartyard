<?php declare(strict_types=1);

namespace Selpol\Feature\Schedule\Internal\Statement;

enum StatementResult
{
    case Success;
    case Error;
    case Critical;
}
