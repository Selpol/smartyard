<?php declare(strict_types=1);

namespace Selpol\Controller\Admin;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\PlogCamshotRequest;
use Selpol\Controller\Request\Admin\PlogIndexRequest;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Feature\File\FileFeature;
use Selpol\Feature\Plog\PlogFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Service\AuthService;

#[Controller('/admin/plog')]
readonly class PlogController extends AdminRbtController
{
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

    #[Get('/camshot/{uuid}')]
    public function camshot(PlogCamshotRequest $request, FileFeature $feature): ResponseInterface
    {
        return response()
            ->withHeader('Content-Type', 'image/jpeg')
            ->withBody(stream($feature->getFileStream($feature->fromGUIDv4($request->uuid))));
    }

    public static function scopes(): array
    {
        return [
            'plog-index-get' => '[События] Получить список',
            'plog-camshot-get' => '[События] Получить скриншот'
        ];
    }
}