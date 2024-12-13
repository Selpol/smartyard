<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\GeoIndexRequest;
use Selpol\Feature\Geo\GeoFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Геоадресация
 */
#[Controller('/admin/geo')]
readonly class GeoController extends AdminRbtController
{
    /**
     * Найти адрес по поиску
     */
    #[Get]
    public function index(GeoIndexRequest $request, GeoFeature $feature): ResponseInterface
    {
        $suggestions = $feature->suggestions($request->search, $request->bound);

        if ($suggestions) {
            return self::success(array_map(static function (array $suggestion): array {
                return [
                    'value' => $suggestion['value'],

                    'latitude' => $suggestion['data']['geo_lat'] ?? null,
                    'longitude' => $suggestion['data']['geo_lon'] ?? null,

                    'data' => [
                        'region_fias_id' => $suggestion['data']['region_fias_id'],
                        'area_fias_id' => $suggestion['data']['area_fias_id'],
                        'city_fias_id' => $suggestion['data']['city_fias_id'],
                        'settlement_fias_id' => $suggestion['data']['settlement_fias_id'],
                        'street_fias_id' => $suggestion['data']['street_fias_id'],
                        'house_fias_id' => $suggestion['data']['house_fias_id'],

                        'fias_level' => $suggestion['data']['fias_level']
                    ]
                ];
            }, $suggestions));
        }

        return self::error('Адрес не найден', 404);
    }
}