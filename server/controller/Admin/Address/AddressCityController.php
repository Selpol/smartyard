<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Address;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Address\AddressCityIndexRequest;
use Selpol\Controller\Request\Admin\Address\AddressCityStoreRequest;
use Selpol\Controller\Request\Admin\Address\AddressCityUpdateRequest;
use Selpol\Entity\Model\Address\AddressCity;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Адрес-Город
 */
#[Controller('/admin/address/city')]
readonly class AddressCityController extends AdminRbtController
{
    /**
     * Получить список городов
     */
    #[Get]
    public function index(AddressCityIndexRequest $request): ResponseInterface
    {
        return self::success(AddressCity::fetchPage($request->page, $request->size, criteria()->equal('address_region_id', $request->address_region_id)->equal('address_area_id', $request->address_area_id)));
    }

    /**
     * Получить город
     * 
     * @param int $id Идентификатор города
     */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        $city = AddressCity::findById($id);

        if (!$city) {
            return self::error('Не удалось найти город', 404);
        }

        return self::success($city);
    }

    /**
     * Создать новый город
     */
    #[Post]
    public function store(AddressCityStoreRequest $request): ResponseInterface
    {
        $city = new AddressCity();

        $city->fill($request->all(false));
        $city->insert();

        return self::success($city->address_city_id);
    }

    /**
     * Обновить город
     */
    #[Put('/{id}')]
    public function update(AddressCityUpdateRequest $request): ResponseInterface
    {
        $city = AddressCity::findById($request->id);

        if (!$city) {
            return self::error('Не удалось найти город', 404);
        }

        $city->fill($request->all(false));
        $city->update();

        return self::success();
    }

    /**
     * Удалить город
     * 
     * @param int $id Идентификатор город
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $city = AddressCity::findById($id);

        if (!$city) {
            return self::error('Не удалось найти город', 404);
        }

        $city->delete();

        return self::success();
    }
}