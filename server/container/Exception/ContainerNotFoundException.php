<?php declare(strict_types=1);

namespace Selpol\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Container\Container;

class ContainerNotFoundException extends ContainerException implements NotFoundExceptionInterface
{
    private string $id;

    public function __construct(Container $container, string $id, $message = "")
    {
        parent::__construct($container, $message);

        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}