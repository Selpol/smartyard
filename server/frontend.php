<?php

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\FrontendRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

$kernel = new Kernel(new FrontendRunner());

exit($kernel->bootstrap()->run([]));