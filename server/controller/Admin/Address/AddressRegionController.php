<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Address;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Address\AddressRegionStoreRequest;
use Selpol\Controller\Request\Admin\Address\AddressRegionUpdateRequest;
use Selpol\Controller\Request\PageRequest;
use Selpol\Entity\Model\Address\AddressRegion;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Адрес-Регион
 */
#[Controller('/admin/address/region')]
readonly class AddressRegionController extends AdminRbtController
{
    /**
     * Получить список регионов
     */
    #[Get]
    public function index(PageRequest $request): ResponseInterface
    {
        return self::success(AddressRegion::fetchPage($request->page, $request->size));
    }

    /**
     * Получить регион
     * 
     * @param int $id Идентификатор региона
     */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        $region = AddressRegion::findById($id);

        if (!$region) {
            return self::error('Не удалось найти регион', 404);
        }

        return self::success($region);
    }

    /**
     * Создать новый регион
     */
    #[Post]
    public function store(AddressRegionStoreRequest $request): ResponseInterface
    {
        $region = new AddressRegion();

        $region->fill($request->all(false));
        $region->insert();

        return self::success($region->address_region_id);
    }

    /**
     * Обновить регион
     */
    #[Put('/{id}')]
    public function update(AddressRegionUpdateRequest $request): ResponseInterface
    {
        $region = AddressRegion::findById($request->id);

        if (!$region) {
            return self::error('Не удалось найти регион', 404);
        }

        $region->fill($request->all(false));
        $region->update();

        return self::success();
    }

    /**
     * Удалить регион
     * 
     * @param int $id Идентификатор региона
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $region = AddressRegion::findById($id);

        if (!$region) {
            return self::error('Не удалось найти регион', 404);
        }

        $region->delete();

        return self::success();
    }
}