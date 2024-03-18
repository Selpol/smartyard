<?php declare(strict_types=1);

namespace Selpol\Middleware\Mobile;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Entity\Model\Block\FlatBlock;
use Selpol\Feature\Block\BlockFeature;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Framework\Router\Route\Route;
use Selpol\Framework\Router\Route\RouteMiddleware;
use Selpol\Service\AuthService;

readonly class BlockFlatMiddleware extends RouteMiddleware
{
    private ?string $house;
    private ?string $flat;

    /**
     * @var int[]
     */
    private array $services;

    public function __construct(array $config)
    {
        $this->house = array_key_exists('house', $config) ? $config['house'] : null;

        if ($this->house) {
            if (array_key_exists('flat', $config)) $this->flat = $config['flat'];
            else $this->flat = null;
        } else if (array_key_exists('flat', $config)) $this->flat = $config['flat'];
        else $this->flat = 'flat_id';

        $this->services = $config['services'];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Route|null $route */
        $route = $request->getAttribute('route');

        if (!$route)
            throw new KernelException('Ошибка проверки доступа', '', 500);

        $value = $route->toArray();

        if ($this->flat && array_key_exists($this->flat, $value) && !is_null($value[$this->flat])) {
            $flatId = rule()->id()->onItem($this->flat, $value);

            if ($block = container(BlockFeature::class)->getFirstBlockForFlat($flatId, $this->services))
                return json_response(403, body: ['code' => 403, 'message' => 'Сервис не доступен по причине блокировки.' . ($block->cause ? (' ' . $block->cause) : '')]);
        } else if ($this->house && array_key_exists($this->house, $value) && !is_null($value[$this->house])) {
            $houseId = rule()->id()->onItem($this->house, $value);

            $flats = array_filter(container(AuthService::class)->getUserOrThrow()->getOriginalValue()['flats'], static fn(array $flat) => $flat['addressHouseId'] == $houseId);
            $flats = array_map(fn(array $flat) => container(BlockFeature::class)->getFirstBlockForFlat($flat['flatId'], $this->services), $flats);

            $blockFlats = array_filter($flats, static fn(?FlatBlock $block) => $block != null);

            if (count($flats) == count($blockFlats))
                return json_response(403, body: ['code' => 403, 'message' => 'Сервис не доступен по причине блокировки.' . ($blockFlats[0]->cause ? (' ' . $blockFlats[0]->cause) : '')]);
        } else return json_response(403, body: ['code' => 403, 'message' => 'Не удалось определить квартиру']);

        return $handler->handle($request);
    }
}