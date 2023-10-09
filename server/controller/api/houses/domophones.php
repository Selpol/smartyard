<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;
    use Selpol\Device\Ip\Intercom\IntercomModel;
    use Selpol\Feature\House\HouseFeature;
    use Selpol\Feature\Sip\SipFeature;

    /**
     * domophones method
     */
    class domophones extends api
    {

        public static function GET($params)
        {
            $households = container(HouseFeature::class);

            if (!$households) {
                return api::ERROR();
            } else {
                $response = [
                    "domophones" => $households->getDomophones(),
                    "models" => IntercomModel::modelsToArray(),
                    "servers" => container(SipFeature::class)->server('all'),
                ];

                return api::ANSWER($response, "domophones");
            }
        }

        public static function index()
        {
            return [
                "GET" => "[Дом] Получить список домофонов",
            ];
        }
    }
}