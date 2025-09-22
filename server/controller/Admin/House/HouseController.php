<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\House;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\HouseDeleteRequest;
use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Feature\House\HouseFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Service\AuthService;

/**
 * Дом
 */
#[Controller('/admin/house/{id}')]
readonly class HouseController extends AdminRbtController
{
    /**
     * Получить дом
     *
     * @param int $id Идентификатор дома
     */
    #[Get]
    public function index(int $id): ResponseInterface
    {
        $house = AddressHouse::findById($id);

        if (!$house) {
            return self::error('Не удалось найти дома', 404);
        }

        return self::success($house);
    }

    /**
     * Удалить дом
     */
    #[Delete]
    public function delete(HouseDeleteRequest $request, AuthService $service, HouseFeature $feature): ResponseInterface
    {
        if (!$service->checkPassword($request->password)) {
            return self::error('Не верный пароль для пользователя', 403);
        }

        if ($feature->destroyHouse($request->id)) {
            return self::success();
        }

        return self::error('Не удалось удалить дом', 404);
    }
}