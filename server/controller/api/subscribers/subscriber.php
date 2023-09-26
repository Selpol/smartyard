<?php

/**
 * subscribers api
 */

namespace api\subscribers {

    use api\api;
    use Selpol\Feature\House\HouseFeature;

    /**
     * subscriber method
     */
    class subscriber extends api
    {
        public static function GET($params)
        {
            $households = container(HouseFeature::class);

            $subscribers = $households->getSubscribers('id', $params['_id']);

            if ($subscribers && count($subscribers) === 1)
                return api::ANSWER($subscribers[0]);

            return api::ERROR();
        }

        public static function POST($params)
        {
            $households = container(HouseFeature::class);

            $subscriberId = $households->addSubscriber($params["mobile"], @$params["subscriberName"], @$params["subscriberPatronymic"], null, @$params["flatId"], @$params["message"]);

            return api::ANSWER($subscriberId, ($subscriberId !== false) ? "subscriber" : false);
        }

        public static function PUT($params)
        {
            $households = container(HouseFeature::class);

            $success = $households->modifySubscriber($params["_id"], $params)
                && $households->setSubscriberFlats($params["_id"], $params["flats"]);

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            if (array_key_exists('force', $params) && $params['force'])
                return api::ANSWER(container(HouseFeature::class)->deleteSubscriber($params['subscriberId']));

            return api::ANSWER(container(HouseFeature::class)->removeSubscriberFromFlat($params["_id"], $params["subscriberId"]));
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
                "PUT" => "#same(addresses,house,PUT)",
                "POST" => "#same(addresses,house,POST)",
                "DELETE" => "#same(addresses,house,DELETE)",
            ];
        }
    }
}