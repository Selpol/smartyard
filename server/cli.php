<?php declare(strict_types=1);

use Selpol\Kernel\Kernel;
use Selpol\Kernel\Runner\CliRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

exit((new Kernel())->setRunner(new CliRunner($argv))->bootstrap()->run());