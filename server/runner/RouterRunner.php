<?php

namespace Selpol\Runner;

use Selpol\Framework\Router\Route\Route;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Framework\Router\Trait\EmitTrait;
use Selpol\Framework\Router\Trait\HandlerTrait;
use Selpol\Framework\Router\Trait\RouterTrait;
use Selpol\Framework\Runner\RunnerExceptionHandlerInterface;
use Selpol\Framework\Runner\RunnerInterface;
use Selpol\Framework\Runner\Trait\LoggerRunnerTrait;
use Selpol\Service\Exception\DatabaseException;
use Selpol\Framework\Validator\Exception\ValidatorException;
use Throwable;

class RouterRunner implements RunnerInterface, RunnerExceptionHandlerInterface, RequestHandlerInterface
{
    use LoggerRunnerTrait;

    use RouterTrait;
    use HandlerTrait;

    use EmitTrait {
        emit as frontendEmit;
    }

    /** @var string[] $middlewares */
    private array $middlewares = [];

    public function __construct()
    {
        $this->setLogger(file_logger('router'));
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    public function run(array $arguments): int
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return $this->emit(response(204));
        }

        $request = server_request($_SERVER['REQUEST_METHOD'], $_SERVER["REQUEST_URI"], $_SERVER);

        $request->withParsedBody(parse_body($request));

        kernel()->getContainer()->set(ServerRequestInterface::class, $request);

        $this->loadRouter(router: $arguments['router']);

        $route = $this->getRouterValue($request);

        if ($route instanceof Route) {
            return $this->emit($this->handle($request));
        }

        return $this->emit(rbt_response(404));
    }

    public function error(Throwable $throwable): int
    {
        try {
            if ($throwable instanceof KernelException) {
                $response = rbt_response($throwable->getCode() ?: 500, $throwable->getLocalizedMessage());
            } elseif ($throwable instanceof ValidatorException) {
                $response = rbt_response(400, $throwable->getValidatorMessage()->message);
                file_logger('response_400')->error($throwable);
            } elseif ($throwable instanceof DatabaseException) {
                if ($throwable->isUniqueViolation()) {
                    $response = rbt_response(400, 'Дубликат объекта');
                } elseif ($throwable->isForeignViolation()) {
                    $response = rbt_response(400, 'Объект имеет дочерние зависимости');
                } else {
                    $response = rbt_response(500);
                }
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
        $this->frontendEmit(
            $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', '*')
                ->withHeader('Access-Control-Allow-Methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])
                ->withHeader('X-Content-Type-Options', 'nosniff')
        );

        return 0;
    }
}