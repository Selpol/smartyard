<?php declare(strict_types=1);

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\CliRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

exit((new Kernel(new CliRunner()))->bootstrap()->run($argv));