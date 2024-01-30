<?php

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\FrontendRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

exit((new Kernel(new FrontendRunner()))->bootstrap()->run([]));