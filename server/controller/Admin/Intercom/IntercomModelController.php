<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Device\Ip\Intercom\IntercomCms;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Домофон-Модель
 */
#[Controller('/admin/intercom/model')]
readonly class IntercomModelController extends AdminRbtController
{
    /**
     * Получить список моделей домофона
     */
    #[Get]
    public function index(): ResponseInterface
    {
        return self::success(IntercomModel::modelsToArray());
    }

    /**
     * Получить список моделей КМС
     */
    #[Get('/cms')]
    public function cms(): ResponseInterface
    {
        return self::success(IntercomCms::modelsToArray());
    }
}
