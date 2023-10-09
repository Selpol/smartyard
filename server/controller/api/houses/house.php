<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;
    use Selpol\Device\Ip\Intercom\IntercomCms;
    use Selpol\Device\Ip\Intercom\IntercomModel;
    use Selpol\Feature\House\HouseFeature;
    use Selpol\Task\Tasks\Intercom\Key\IntercomHouseKeyTask;

    /**
     * house method
     */
    class house extends api
    {
        public static function GET($params)
        {
            $households = container(HouseFeature::class);

            $flats = $households->getFlats("houseId", $params["_id"]);

            if ($flats)
                usort($flats, static fn(array $a, array $b) => $a['flat'] > $b['flat'] ? 1 : -1);

            $house = [
                "flats" => $flats,
                "entrances" => $households->getEntrances("houseId", $params["_id"]),
                "cameras" => $households->getCameras("houseId", $params["_id"]),
                "domophoneModels" => IntercomModel::modelsToArray(),
                "cmses" => IntercomCms::modelsToArray(),
            ];

            $house = ($house["flats"] !== false && $house["entrances"] !== false && $house["domophoneModels"] !== false && $house["cmses"] !== false) ? $house : false;

            return api::ANSWER($house, "house");
        }

        public static function POST($params)
        {
            $households = container(HouseFeature::class);

            $houseId = $params['_id'];
            $keys = $params['keys'];

            foreach ($keys as $key) {
                $households->addKey($key["rfId"], 2, $key["accessTo"], '');
            }

            task(new IntercomHouseKeyTask($houseId));

            return api::ANSWER();
        }

        public static function index()
        {
            return [
                "GET" => "[Дом] Получить дом",
                "POST" => "[Дом] Загрузить ключи"
            ];
        }
    }
}