<?php declare(strict_types=1);

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\TaskRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

$kernel = new Kernel(new TaskRunner());

exit($kernel->bootstrap()->run($argv));