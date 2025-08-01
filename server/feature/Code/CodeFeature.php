<?php

declare(strict_types=1);

namespace Selpol\Feature\Code;

use Selpol\Cli\Cron\CronInterface;
use Selpol\Cli\Cron\CronTag;
use Selpol\Feature\Code\Internal\InternalCodeFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[CronTag]
#[Singleton(InternalCodeFeature::class)]
readonly abstract class CodeFeature extends Feature implements CronInterface {}
