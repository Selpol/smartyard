<?php declare(strict_types=1);

use Selpol\Framework\Kernel\Kernel;
use Selpol\Runner\CliRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

$kernel = new Kernel(new CliRunner());

$kernel->getRunner()->setLogger(stack_logger([echo_logger(), file_logger('cli')]));
$kernel->bootstrap();

exit($kernel->run($argv));