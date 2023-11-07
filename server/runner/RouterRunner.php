<?php

namespace Selpol\Runner;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Framework\Kernel\Trait\LoggerKernelTrait;
use Selpol\Framework\Router\Trait\EmitTrait;
use Selpol\Framework\Router\Trait\HandlerTrait;
use Selpol\Framework\Router\Trait\RouterTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Validator\Exception\ValidatorException;
use Throwable;

class RouterRunner implements RunnerInterface, RunnerExceptionHandlerInterface, RequestHandlerInterface
{
    use LoggerKernelTrait;

    use RouterTrait;
    use HandlerTrait;

    use EmitTrait {
        emit as frontendEmit;
    }

    /** @var string[] $middlewares */
    private array $middlewares = [];

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    function run(array $arguments): int
    {
        $request = server_request($_SERVER['REQUEST_METHOD'], $_SERVER["REQUEST_URI"], $_SERVER);

        $request->withParsedBody(parse_body($request));

        kernel()->getContainer()->set(ServerRequestInterface::class, $request);

        $this->loadRouter();

        $route = $this->getRouterValue($request);

        if ($route !== null)
            return $this->emit($this->handle($request));

        return $this->emit(rbt_response(404));
    }

    function error(Throwable $throwable): int
    {
        try {
            if ($throwable instanceof KernelException)
                $response = rbt_response($throwable->getCode() ?: 500, $throwable->getLocalizedMessage());
            else if ($throwable instanceof ValidatorException) {
                $response = rbt_response(400, $throwable->getValidatorMessage()->message);

                file_logger('response_400')->error($throwable);
            } else {
                file_logger('response')->error($throwable);

                $response = rbt_response(500);
            }

            return $this->emit($response);
        } catch (Throwable $throwable) {
            file_logger('response')->critical($throwable);

            return 1;
        }
    }

    protected function emit(ResponseInterface $response): int
    {
        $this->frontendEmit($response);

        return 0;
    }
}