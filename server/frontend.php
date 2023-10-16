<?php

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\FrontendRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

$kernel = new Kernel(new FrontendRunner());

$kernel->getRunner()->setLogger(file_logger('frontend'));

exit($kernel->bootstrap()->run([]));