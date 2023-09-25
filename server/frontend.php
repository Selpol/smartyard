<?php

use Selpol\Kernel\Kernel;
use Selpol\Kernel\Runner\FrontendRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

exit((new Kernel())->setRunner(new FrontendRunner())->bootstrap()->run());