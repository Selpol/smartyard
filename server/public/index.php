<?php

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\RouterRunner;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';

$kernel = new Kernel(new RouterRunner());

exit($kernel->bootstrap()->run(['router' => 'router']));