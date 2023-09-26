<?php declare(strict_types=1);

namespace Selpol\Validator\Exception;

use Exception;
use Selpol\Validator\ValidatorMessage;
use Throwable;

class ValidatorException extends Exception
{
    protected ValidatorMessage $validatorMessage;

    public function __construct(ValidatorMessage $validatorMessage, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->validatorMessage = $validatorMessage;
    }

    public function getValidatorMessage(): ValidatorMessage
    {
        return $this->validatorMessage;
    }
}