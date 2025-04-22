<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Address;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Address\AddressHouseIndexRequest;
use Selpol\Controller\Request\Admin\Address\AddressHouseMagicRequest;
use Selpol\Controller\Request\Admin\Address\AddressHouseStoreRequest;
use Selpol\Controller\Request\Admin\Address\AddressHouseUpdateRequest;
use Selpol\Controller\Request\Admin\Address\AddressHouseQrRequest;
use Selpol\Entity\Model\Address\AddressArea;
use Selpol\Entity\Model\Address\AddressCity;
use Selpol\Entity\Model\Address\AddressHouse;
use Selpol\Entity\Model\Address\AddressRegion;
use Selpol\Entity\Model\Address\AddressSettlement;
use Selpol\Entity\Model\Address\AddressStreet;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\Geo\GeoFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Framework\Router\Attribute\Method\Put;
use Selpol\Task\Tasks\QrTask;
use Throwable;

/**
 * Адрес-Дом
 */
#[Controller('/admin/address/house')]
readonly class AddressHouseController extends AdminRbtController
{
    /**
     * Получить список домов
     */
    #[Get]
    public function index(AddressHouseIndexRequest $request): ResponseInterface
    {
        return self::success(AddressHouse::fetchPage($request->page, $request->size, criteria()->in('address_house_id', $request->ids)->equal('address_settlement_id', $request->address_settlement_id)->equal('address_street_id', $request->address_street_id)));
    }

    /**
     * Получить дом
     * 
     * @param int $id Идентификатор дома
     */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        $house = AddressHouse::findById($id);

        if (!$house) {
            return self::error('Не удалось найти дом', 404);
        }

        return self::success($house);
    }

    /**
     * Получить QR с адреса
     */
    #[Get('/qr/{id}')]
    public function qr(AddressHouseQrRequest $request, FileFeature $feature): ResponseInterface
    {
        set_time_limit(480);

        $house = AddressHouse::findById($request->id);

        if (!$house) {
            return self::error('Не удалось найти дом', 404);
        }

        $uuid = task(new QrTask($request->id, $request->override))->sync();

        $file = $feature->getFile($uuid);

        return response()
            ->withBody($file->stream)
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $house->house_full . '.zip"');
    }

    /**
     * Создать новый дом
     */
    #[Post]
    public function store(AddressHouseStoreRequest $request): ResponseInterface
    {
        $house = new AddressHouse();

        $house->fill($request->all(false));
        $house->insert();

        return self::success($house->address_house_id);
    }

    /**
     * Автоматически создать дом
     */
    #[Post('/magic')]
    public function magic(AddressHouseMagicRequest $request, GeoFeature $feature): ResponseInterface
    {
        try {
            $result = $feature->suggestions($request->address, 'house');

            if (count($result) == 0) {
                return self::error('Не удалось определить дом', 404);
            }
    
            if ($result[0]['data']['fias_level'] !== '8') {
                return self::error('Не верные данные', 400);
            }
    
            $house = $result[0];
    
            $regionId = null;
    
            if ($house["data"]["region_fias_id"]) {
                $region = AddressRegion::fetch(criteria()->equal('region_uuid', $house['data']['region_fias_id'])->equal('region', $house['data']['region']));
    
                if (!$region) {
                    $region = new AddressRegion();
    
                    $region->region_uuid = $house['data']['region_fias_id'];
                    $region->region_iso_code = $house['data']['region_iso_code'];
                    $region->region_with_type = $house['data']['region_with_type'];
                    $region->region_type = $house['data']['region_type'];
                    $region->region_type_full = $house['data']['region_type_full'];
                    $region->region = $house['data']['region'];
    
                    $region->insert();
                }
    
                $regionId = $region->address_region_id;
            }
    
            if (!$regionId) {
                return self::error('Не удалось разобрать регион', 400);
            }
    
            $areaId = null;
    
            if ($house["data"]["area_fias_id"]) {
                $area = AddressArea::fetch(criteria()->equal('area_uuid', $house['data']['area_fias_id']));
    
                if (!$area) {
                    $area = new AddressArea();
    
                    $area->address_region_id = $regionId;
    
                    $area->area_uuid = $house['data']['area_fias_id'];
                    $area->area_with_type = $house['data']['area_with_type'];
                    $area->area_type = $house['data']['area_type'];
                    $area->area_type_full = $house['data']['area_type_full'];
                    $area->area = $house['data']['area'];
    
                    $area->insert();
                }
    
                $areaId = $area->address_area_id;
            }
    
            if ($areaId) {
                $regionId = null;
            }
    
            $cityId = null;
    
            if ($house["data"]["city_fias_id"]) {
                $city = AddressCity::fetch(criteria()->equal('city_uuid', $house['data']['city_fias_id']));
    
                file_logger('house')->debug('', [$city]);

                if (!$city) {
                    $city = new AddressCity();
    
                    $city->address_region_id = $regionId;
                    $city->address_area_id = $areaId;
    
                    $city->city_uuid = $house['data']['city_fias_id'];
                    $city->city_with_type = $house['data']['city_with_type'];
                    $city->city_type = $house['data']['city_type'];
                    $city->city_type_full = $house['data']['city_type_full'];
                    $city->city = $house['data']['city'];
    
                    $city->insert();
                }
    
                $cityId = $city->address_city_id;
            }
    
            if ($cityId) {
                $areaId = null;
            }
    
            if (!$areaId && !$cityId) {
                return self::error('Не удалось разобрать область или город', 400);
            }
    
            $settlementId = null;
    
            if ($house["data"]["settlement_fias_id"]) {
                $settlement = AddressSettlement::fetch(criteria()->equal('settlement_uuid', $house['data']['settlement_fias_id']));
    
                if (!$settlement) {
                    $settlement = new AddressSettlement();
    
                    $settlement->address_area_id = $areaId;
                    $settlement->address_city_id = $cityId;
    
                    $settlement->settlement_uuid = $house['data']['settlement_fias_id'];
                    $settlement->settlement_with_type = $house['data']['settlement_with_type'];
                    $settlement->settlement_type = $house['data']['settlement_type'];
                    $settlement->settlement_type_full = $house['data']['settlement_type_full'];
                    $settlement->settlement = $house['data']['settlement'];
    
                    $settlement->insert();
                }
    
                $settlementId = $settlement->address_settlement_id;
            }
    
            if ($settlementId) {
                $cityId = null;
            }
    
            if (!$cityId && !$settlementId) {
                return self::error('Не удалось разобрать город или поселение', 400);
            }
    
            $streetId = null;
    
            if ($house["data"]["street_fias_id"]) {
                $street = AddressStreet::fetch(criteria()->equal('street_uuid', $house['data']['street_fias_id']));
    
                if (!$street) {
                    $street = new AddressStreet();
    
                    $street->address_city_id = $cityId;
                    $street->address_settlement_id = $settlementId;
    
                    $street->street_uuid = $house['data']['street_fias_id'];
                    $street->street_with_type = $house['data']['street_with_type'];
                    $street->street_type = $house['data']['street_type'];
                    $street->street_type_full = $house['data']['street_type_full'];
                    $street->street = $house['data']['street'];
    
                    $street->insert();
                }
    
                $streetId = $street->address_street_id;
            }
    
            if ($streetId) {
                $settlementId = null;
            }
    
            if (!$settlementId && !$streetId) {
                return self::error('Не удалось разобрать поселение или улицу', 400);
            }

            $houseId = null;
    
            if ($house["data"]["house_fias_id"]) {
                $addressHouse = AddressHouse::fetch(criteria()->equal('house_uuid', $house['data']['house_fias_id']));
    
                if (!$addressHouse) {
                    $addressHouse = new AddressHouse();
    
                    $addressHouse->address_settlement_id = $settlementId;
                    $addressHouse->address_street_id = $streetId;
    
                    $addressHouse->house_uuid = $house['data']['house_fias_id'];
                    $addressHouse->house_type = $house['data']['house_type'];
                    $addressHouse->house_type_full = $house['data']['house_type_full'];
                    $addressHouse->house_full = $house['value'];
                    $addressHouse->house = $house['data']['house'];
    
                    $addressHouse->insert();
                }
    
                $houseId = $addressHouse->address_house_id;
            }
    
            if (!$houseId) {
                return self::error('Не удалось создать дом', 400);
            }
    
            return self::success($houseId);
        } catch(Throwable $throwable) {
            file_logger('house')->debug($throwable);
            
            return self::error('Ошибка создания дома');
        }
    }

    /**
     * Обновить дом
     */
    #[Put('/{id}')]
    public function update(AddressHouseUpdateRequest $request): ResponseInterface
    {
        $house = AddressHouse::findById($request->id);

        if (!$house) {
            return self::error('Не удалось найти дом', 404);
        }

        $house->fill($request->all(false));
        $house->update();

        return self::success();
    }

    /**
     * Удалить дом
     * 
     * @param int $id Идентификатор дома
     */
    #[Delete('/{id}')]
    public function delete(int $id): ResponseInterface
    {
        $house = AddressHouse::findById($id);

        if (!$house) {
            return self::error('Не удалось найти дом', 404);
        }

        $house->delete();

        return self::success();
    }
}