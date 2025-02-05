<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Device\Ip\Intercom\Setting\Key\KeyInterface;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;

/**
 * Ключи домофона
 */
#[Controller('/admin/intercom/key')]
readonly class IntercomKeyController extends AdminRbtController
{
    /**
     * Получить ключи с домофона
     * @param int $id Идентификатор домофона
     * */
    #[Get('/{id}')]
    public function show(int $id): ResponseInterface
    {
        $intercom = intercom($id);

        if ($intercom) {
            if ($intercom instanceof KeyInterface) {
                return self::success($intercom->getKeys(null));
            }

            return self::error('Домофон не поддерживает ключи', 400);
        }

        return self::error('Домофон не найден', 404);
    }
}
