<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\House;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\House\HouseGsmIndexRequest;
use Selpol\Controller\Request\Admin\House\HouseGsmStoreRequest;
use Selpol\Device\Ip\Intercom\Setting\Gms\GmsInterface;
use Selpol\Entity\Model\House\HouseGsm;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Delete;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;

/**
 * Дом-GSM
 */
#[Controller('/admin/house/gsm')]
readonly class HouseGsmController extends AdminRbtController
{
    /**
     * Получить пользователей GSM
     *
     * @return ResponseInterface
     */
    #[Get]
    public function index(HouseGsmIndexRequest $request): ResponseInterface
    {
        if ($request->intercom_id) {
            $gsms = HouseGsm::fetchPage($request->page, $request->size, criteria()->equal('house_domophone_id', $request->intercom_id));

            return self::success($gsms->withTransform(static function (HouseGsm $gsm): HouseGsm {
                $gsm->__set('phone', $gsm->subscriber()->fetch(setting: setting()->columns(['id']))->id);

                return $gsm;
            }));
        } else if ($request->subscriber_id) {
            $gsms = HouseGsm::fetchPage($request->page, $request->size, criteria()->equal('house_subscriber_id', $request->subscriber_id));

            if ($gsms->getSize() > 0) {
                $phone = HouseSubscriber::findById($request->subscriber_id, setting: setting()->nonNullable())->id;

                return self::success(static function (HouseGsm $gsm) use ($phone): HouseGsm {
                    $gsm->__set('phone', $phone);

                    return $gsm;
                });
            }

            return self::success($gsms);
        } else {
            return self::error('Не передан GSM или Абонент', 400);
        }
    }

    /**
     * Добавить абонента в GSM
     */
    #[Post]
    public function store(HouseGsmStoreRequest $request, AuditFeature $feature): ResponseInterface
    {
        $gsm = HouseGsm::fetch(criteria()->equal('house_subscriber_id', $request->subscriber_id)->equal('house_domophone_id', $request->intercom_id));

        if (!$gsm) {
            $gsm = new HouseGsm();

            $gsm->house_domophone_id = $request->intercom_id;
            $gsm->house_subscriber_id = $request->subscriber_id;

            $gsm->count = 0;

            $gsm->insert();
        }

        $subscriber = $gsm->subscriber;
        $intercom = $gsm->intercom;

        if ($intercom instanceof GmsInterface) {
            $intercom->addPhone($subscriber->id);

            $gsm->count += 1;
            $gsm->update();
        }

        if ($feature->canAudit()) {
            $feature->audit((string) $gsm->id, HouseGsm::class, 'insert', 'Добавление номера телефона (' . $subscriber->house_subscriber_id . '/' . $subscriber->id . ') на ' . $intercom->house_domophone_id);
        }

        return self::success();
    }

    /**
     * Удалить абонента с GSM
     * 
     * @param int $id Идентификатор абонента в GSM
     */
    #[Delete('/{id}')]
    public function delete(int $id, AuditFeature $feature): ResponseInterface
    {
        $gsm = HouseGsm::findById($id, setting: setting()->nonNullable());

        $subscriber = $gsm->subscriber;
        $intercom = $gsm->intercom;

        if ($intercom instanceof GmsInterface) {
            $intercom->removePhone($subscriber->id);
        }

        if ($feature->canAudit()) {
            $feature->audit((string) $gsm->id, HouseGsm::class, 'delete', 'Удаление номера телефона ' . $subscriber->id . ' с ' . $intercom->house_domophone_id);
        }

        return self::success();
    }
}