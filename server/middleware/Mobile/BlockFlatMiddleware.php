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

    private int $code;
    
    private ?array $body;

    /**
     * @var int[]
     */
    private array $services;

    public function __construct(array $config)
    {
        $this->house = $config['house'] ?? null;

        if ($this->house) {
            $this->flat = $config['flat'] ?? null;
        } elseif (array_key_exists('flat', $config)) {
            $this->flat = $config['flat'];
        } else {
            $this->flat = 'flat_id';
        }

        $this->services = $config['services'];

        $this->code = array_key_exists('code', $config) ? $config['code'] : 403;
        $this->body = $config['body'] ?? null;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Route|null $route */
        $route = $request->getAttribute('route');

        if (!$route) {
            throw new KernelException('Ошибка проверки доступа', '', 500);
        }

        $value = $route->toArray();

        if ($this->flat && array_key_exists($this->flat, $value) && !is_null($value[$this->flat])) {
            $flatId = rule()->id()->onItem($this->flat, $value);
            if ($block = container(BlockFeature::class)->getFirstBlockForFlat($flatId, $this->services)) {
                return json_response($this->code, body: $this->body !== null && $this->body !== [] ? $this->body : ['code' => 403, 'message' => 'Сервис не доступен по причине блокировки.' . ($block->cause ? (' ' . $block->cause) : '')]);
            }
        } elseif ($this->house && array_key_exists($this->house, $value) && !is_null($value[$this->house])) {
            $houseId = rule()->id()->onItem($this->house, $value);
            $flats = array_filter(container(AuthService::class)->getUserOrThrow()->getOriginalValue()['flats'], static fn(array $flat): bool => $flat['addressHouseId'] == $houseId);
            $flats = array_map(fn(array $flat) => container(BlockFeature::class)->getFirstBlockForFlat($flat['flatId'], $this->services), $flats);
            $blockFlats = array_filter($flats, static fn(?FlatBlock $block): bool => $block != null);
            if (count($flats) === count($blockFlats)) {
                return json_response($this->code, body: $this->body !== null && $this->body !== [] ? $this->body : ['code' => 403, 'message' => 'Сервис не доступен по причине блокировки.' . ($blockFlats[0]->cause ? (' ' . $blockFlats[0]->cause) : '')]);
            }
        } else {
            return json_response($this->code, body: $this->body !== null && $this->body !== [] ? $this->body : ['code' => 403, 'message' => 'Не удалось определить квартиру']);
        }

        return $handler->handle($request);
    }
}