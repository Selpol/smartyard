<?php

use Selpol\Kernel\Kernel;
use Selpol\Kernel\Runner\FrontendRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

require_once path('/controller/api/api.php');

$kernel = new Kernel();

exit($kernel->setRunner(new FrontendRunner())->bootstrap()->run());