<?php

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\RouterRunner;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';

$kernel = new Kernel(new RouterRunner());

$kernel->getRunner()->setLogger(file_logger('index'));

exit($kernel->bootstrap()->run([]));