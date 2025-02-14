<?php declare(strict_types=1);

namespace Selpol\Cli\Cron;

use Attribute;
use Selpol\Framework\Container\Attribute\Tag;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class CronTag extends Tag
{
    public const CRON = 'Cron';

    public function __construct()
    {
        parent::__construct(CronTag::CRON);
    }
}