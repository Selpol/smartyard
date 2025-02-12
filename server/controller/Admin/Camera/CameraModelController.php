<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Camera;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Камера-Модель
 */
#[Controller('/admin/camera/model')]
readonly class CameraModelController extends AdminRbtController
{
    /**
     * Получить список моделей камер
     */
    #[Get]
    public function index(): ResponseInterface
    {
        return self::success(CameraModel::models());
    }
}
