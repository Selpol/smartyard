<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Address;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Address\AddressStreetIndexRequest;
use Selpol\Controller\Request\Admin\Address\AddressStreetStoreRequest;
use Selpol\Controller\Request\Admin\Address\AddressStreetUpdateRequest;
use Selpol\Entity\Model\Address\AddressStreet;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Адрес-Улица
 */
#[Controller('/admin/address/street')]
readonly class AddressStreetController extends AdminRbtController
{
    /**
     * Получить список улиц
     */
    #[Get]
    public function index(AddressStreetIndexRequest $request): ResponseInterface
    {
        return self::success(AddressStreet::fetchPage($request->page, $request->size, criteria()->equal('address_city_id', $request->address_city_id)->equal('address_settlement_id', $request->address_settlement_id)));
    }

    /**
     * Получить улицу
     * 
     * @param int $id Идентификатор улицы
     */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        $street = AddressStreet::findById($id);

        if (!$street) {
            return self::error('Не удалось найти улицу', 404);
        }

        return self::success($street);
    }

    /**
     * Создать новую улицу
     */
    #[Post]
    public function store(AddressStreetStoreRequest $request): ResponseInterface
    {
        $street = new AddressStreet();

        $street->fill($request->all(false));
        $street->insert();

        return self::success($street->address_street_id);
    }

    /**
     * Обновить улицу
     */
    #[Put('/{id}')]
    public function update(AddressStreetUpdateRequest $request): ResponseInterface
    {
        $street = AddressStreet::findById($request->id);

        if (!$street) {
            return self::error('Не удалось найти улицу', 404);
        }

        $street->fill($request->all(false));
        $street->update();

        return self::success();
    }

    /**
     * Удалить улицу
     * 
     * @param int $id Идентификатор улицы
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $street = AddressStreet::findById($id);

        if (!$street) {
            return self::error('Не удалось найти улицу', 404);
        }

        $street->delete();

        return self::success();
    }
}