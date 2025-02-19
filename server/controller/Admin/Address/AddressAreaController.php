<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Address;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Address\AddressAreaIndexRequest;
use Selpol\Controller\Request\Admin\Address\AddressAreaStoreRequest;
use Selpol\Controller\Request\Admin\Address\AddressAreaUpdateRequest;
use Selpol\Entity\Model\Address\AddressArea;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Адрес-Область
 */
#[Controller('/admin/address/area')]
readonly class AddressAreaController extends AdminRbtController
{
    /**
     * Получить список областей
     */
    #[Get]
    public function index(AddressAreaIndexRequest $request): ResponseInterface
    {
        return self::success(AddressArea::fetchPage($request->page, $request->size, criteria()->equal('address_region_id', $request->address_region_id)));
    }

    /**
     * Получить область
     * 
     * @param int $id Идентификатор области
     */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        $area = AddressArea::findById($id);

        if (!$area) {
            return self::error('Не удалось найти область', 404);
        }

        return self::success($area);
    }

    /**
     * Создать новую область
     */
    #[Post]
    public function store(AddressAreaStoreRequest $request): ResponseInterface
    {
        $area = new AddressArea();

        $area->fill($request->all(false));
        $area->insert();

        return self::success($area->address_area_id);
    }

    /**
     * Обновить область
     */
    #[Put('/{id}')]
    public function update(AddressAreaUpdateRequest $request): ResponseInterface
    {
        $area = AddressArea::findById($request->id);

        if (!$area) {
            return self::error('Не удалось найти область', 404);
        }

        $area->fill($request->all(false));
        $area->update();

        return self::success();
    }

    /**
     * Удалить область
     * 
     * @param int $id Идентификатор области
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $area = AddressArea::findById($id);

        if (!$area) {
            return self::error('Не удалось найти область', 404);
        }

        $area->delete();

        return self::success();
    }
}