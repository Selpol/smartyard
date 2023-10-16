<?php declare(strict_types=1);

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\MqttRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

$kernel = new Kernel(new MqttRunner());

$kernel->getRunner()->setLogger(file_logger('mqtt'));

exit($kernel->bootstrap()->run([]));