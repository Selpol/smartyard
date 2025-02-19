<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Address;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Address\AddressSettlementIndexRequest;
use Selpol\Controller\Request\Admin\Address\AddressSettlementStoreRequest;
use Selpol\Controller\Request\Admin\Address\AddressSettlementUpdateRequest;
use Selpol\Entity\Model\Address\AddressSettlement;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;

/**
 * Адрес-Поселение
 */
#[Controller('/admin/address/settlement')]
readonly class AddressSettlementController extends AdminRbtController
{
    /**
     * Получить список поселений
     */
    #[Get]
    public function index(AddressSettlementIndexRequest $request): ResponseInterface
    {
        return self::success(AddressSettlement::fetchPage($request->page, $request->size, criteria()->equal('address_area_id', $request->address_area_id)->equal('address_city_id', $request->address_city_id)));
    }

    /**
     * Получить поселение
     * 
     * @param int $id Идентификатор поселения
     */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        $settlement = AddressSettlement::findById($id);

        if (!$settlement) {
            return self::error('Не удалось найти поселение', 404);
        }

        return self::success($settlement);
    }

    /**
     * Создать новое поселение
     */
    #[Post]
    public function store(AddressSettlementStoreRequest $request): ResponseInterface
    {
        $settlement = new AddressSettlement();

        $settlement->fill($request->all(false));
        $settlement->insert();

        return self::success($settlement->address_settlement_id);
    }

    /**
     * Обновить поселение
     */
    #[Put('/{id}')]
    public function update(AddressSettlementUpdateRequest $request): ResponseInterface
    {
        $settlement = AddressSettlement::findById($request->id);

        if (!$settlement) {
            return self::error('Не удалось найти поселение', 404);
        }

        $settlement->fill($request->all(false));
        $settlement->update();

        return self::success();
    }

    /**
     * Удалить поселение
     * 
     * @param int $id Идентификатор поселения
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $settlement = AddressSettlement::findById($id);

        if (!$settlement) {
            return self::error('Не удалось найти поселение', 404);
        }

        $settlement->delete();

        return self::success();
    }
}