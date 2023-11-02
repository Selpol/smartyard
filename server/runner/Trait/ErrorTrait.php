<?php declare(strict_types=1);

namespace Selpol\Runner\Trait;

use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Validator\Exception\ValidatorException;
use Throwable;

trait ErrorTrait
{
    function error(Throwable $throwable): int
    {
        try {
            if ($throwable instanceof KernelException)
                $response = rbt_response($throwable->getCode() ?: 500, $throwable->getLocalizedMessage());
            else if ($throwable instanceof ValidatorException)
                $response = rbt_response(400, $throwable->getValidatorMessage()->message);
            else {
                file_logger('response')->error($throwable);

                $response = rbt_response(500);
            }

            return $this->emit($response);
        } catch (Throwable $throwable) {
            file_logger('response')->critical($throwable);

            return 1;
        }
    }
}