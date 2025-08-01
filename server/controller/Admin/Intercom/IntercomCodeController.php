<?php

declare(strict_types=1);

namespace Selpol\Controller\Admin\Intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Device\Ip\Intercom\Setting\Code\CodeInterface;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Коды домофона
 */
#[Controller('/admin/intercom/code')]
readonly class IntercomCodeController extends AdminRbtController
{
    /**
     * Получить коды с домофона
     * @param int $id Идентификатор домофона
     * */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        $intercom = intercom($id);

        if ($intercom) {
            if ($intercom instanceof CodeInterface) {
                return self::success($intercom->getCodes(null));
            }

            return self::error('Домофон не поддерживает коды', 400);
        }

        return self::error('Домофон не найден', 404);
    }
}
