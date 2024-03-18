<?php declare(strict_types=1);

namespace Selpol\Middleware\Mobile;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Framework\Router\Route\Route;
use Selpol\Framework\Router\Route\RouteMiddleware;
use Selpol\Service\AuthService;

readonly class FlatMiddleware extends RouteMiddleware
{
    private ?int $role;

    private ?string $house;
    private ?string $flat;

    public function __construct(array $config)
    {
        $this->role = array_key_exists('role', $config) ? $config['role'] : null;

        $this->house = array_key_exists('house', $config) ? $config['house'] : null;

        if ($this->house) {
            if (array_key_exists('flat', $config)) $this->flat = $config['flat'];
            else $this->flat = null;
        } else if (count($config) === 1) $this->flat = $config[0];
        else $this->flat = 'flat_id';
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Route|null $route */
        $route = $request->getAttribute('route');

        if (!$route)
            throw new KernelException('Ошибка проверки доступа', '', 500);

        $flats = array_reduce(container(AuthService::class)->getUserOrThrow()->getOriginalValue()['flats'], static function (array $previous, array $current) {
            $previous[$current['flatId']] = $current;

            return $previous;
        }, []);

        $value = $route->toArray();

        if ($this->flat && array_key_exists($this->flat, $value) && !is_null($value[$this->flat])) {
            $flatId = rule()->id()->onItem($this->flat, $value);

            if (!array_key_exists($flatId, $flats))
                return json_response(403, body: ['code' => 403, 'message' => 'Нету доступа к квартире']);

            if (!is_null($this->role) && $flats[$flatId]['role'] !== $this->role)
                return json_response(403, body: ['code' => 403, 'message' => 'Неверная роль в квартире']);
        } else if ($this->house && array_key_exists($this->house, $value) && !is_null($value[$this->house])) {
            $houseId = rule()->id()->onItem($this->house, $value);

            /** @var array<int, int> $flats */
            $flatAddresses = array_reduce($flats, static function (array $previous, array $current) {
                $previous[$current['flatId']] = $current['addressHouseId'];

                return $previous;
            }, []);

            /** @var array<int> $findFlatIds */
            $findFlatIds = [];

            foreach ($flatAddresses as $flatId => $addressId)
                if ($houseId == $addressId)
                    $findFlatIds[] = $flatId;

            if (count($findFlatIds) == 0)
                return json_response(403, body: ['code' => 403, 'message' => 'Нету доступа к дому']);

            if (!is_null($this->role))
                foreach ($findFlatIds as $flatId)
                    if ($flats[$flatId]['role'] !== $this->role)
                        return json_response(403, body: ['code' => 403, 'message' => 'Неверная роль в одной из квартир в доме']);
        } else return json_response(403, body: ['code' => 403, 'message' => 'Не удалось определить квартиру']);

        return $handler->handle($request);
    }
}