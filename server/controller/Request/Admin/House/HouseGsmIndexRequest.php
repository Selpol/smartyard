<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\House;

use Selpol\Controller\Request\PageRequest;

/**
 * @property-read int|null $intercom_id Идентификатор GSM
 * @property-read int|null $subscriber_id Идентификатор Абонента
 */
readonly class HouseGsmIndexRequest extends PageRequest
{
    public static function getExtendValidate(): array
    {
        return [
            'intercom_id' => rule()->id()->int()->clamp(0),
            'subscriber_id' => rule()->id()->int()->clamp(0),
        ];
    }
}