<?php declare(strict_types=1);

namespace Selpol\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Selpol\Container\Container;

class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
    private Container $container;

    public function __construct(Container $container, $message = "")
    {
        parent::__construct($message);

        $this->container = $container;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}