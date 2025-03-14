<?php

declare(strict_types=1);

namespace Selpol\Controller\Admin;

use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\PlogCamshotRequest;
use Selpol\Controller\Request\Admin\PlogIndexRequest;
use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\File\FileStorage;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Service\AuthService;
use Throwable;

/**
 * События домофона
 */
#[Controller('/admin/plog')]
readonly class PlogController extends AdminRbtController
{
    /**
     * Получить список события на квартире
     */
    #[Get('/{id}')]
    public function index(PlogIndexRequest $request, PlogFeature $feature, AuthService $service): ResponseInterface
    {
        $flat = HouseFlat::findById($request->id, setting: setting()->nonNullable());
        $result = $feature->getEventsByFlat($flat->house_flat_id, $request->type, $request->opened, $request->page, $request->size);

        if ($result) {
            if (!$service->checkScope('mobile-mask')) {
                return self::success(array_map(static function (array $item): array {
                    if (array_key_exists('phones', $item) && is_array($item['phones']) && (array_key_exists('user_phone', $item['phones']) && $item['phones']['user_phone'])) {
                        $item['phones']['user_phone'] = mobile_mask($item['phones']['user_phone']);
                    }

                    return $item;
                }, $result));
            }

            return self::success($result);
        }

        return self::success([]);
    }

    /**
     * Получить список событий на доме
     */
    #[Get('/house/{id}')]
    public function house(PlogIndexRequest $request, PlogFeature $feature, AuthService $service): ResponseInterface
    {
        $house = AddressHouse::findById($request->id, setting: setting()->nonNullable());
        $result = $feature->getEventsByHouse($house, $request->type, $request->opened, $request->page, $request->size);

        if ($result) {
            if (!$service->checkScope('mobile-mask')) {
                return self::success(array_map(static function (array $item): array {
                    if (array_key_exists('phones', $item) && is_array($item['phones']) && (array_key_exists('user_phone', $item['phones']) && $item['phones']['user_phone'])) {
                        $item['phones']['user_phone'] = mobile_mask($item['phones']['user_phone']);
                    }

                    return $item;
                }, $result));
            }

            return self::success($result);
        }

        return self::success([]);
    }

    /**
     * Получить скриншот с события
     */
    #[Get('/camshot/{uuid}')]
    public function camshot(PlogCamshotRequest $request, FileFeature $feature): ResponseInterface
    {
        try {
            $file = $feature->getFile($feature->fromGUIDv4($request->uuid), FileStorage::Screenshot);

            return response()
                ->withHeader('Content-Type', 'image/jpeg')
                ->withBody($file->stream);
        } catch (Throwable $throwable) {
            return self::error('Скриншота устарел', 404);
        }
    }
}
