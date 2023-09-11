<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;
    use Selpol\Task\Tasks\Intercom\IntercomLevelTask;

    /**
     * house method
     */
    class flat extends api
    {
        public static function GET($params)
        {
            $flatId = @$params['_id'];

            if (!isset($flatId))
                return api::ERROR('Неверный формат данных');

            $flat = backend('households')->getFlat($flatId);

            return api::ANSWER($flat, ($flat !== false) ? 'flat' : 'notAcceptable');
        }

        public static function POST($params)
        {
            $households = backend("households");

            $flatId = $households->addFlat($params["houseId"], $params["floor"], $params["flat"], $params["code"], $params["entrances"], $params["apartmentsAndLevels"], $params["manualBlock"], $params["adminBlock"], $params["openCode"], $params["plog"], $params["autoOpen"], $params["whiteRabbit"], $params["sipEnabled"], $params["sipPassword"]);

            return api::ANSWER($flatId, ($flatId !== false) ? "flatId" : "notAcceptable");
        }

        public static function PUT($params)
        {
            $households = backend("households");

            $success = $households->modifyFlat($params["_id"], $params);

            if (array_key_exists('apartmentsAndLevels', $params)) {
                foreach ($params['apartmentsAndLevels'] as $entranceId => $value) {
                    $levels = explode(',', $value['apartmentLevels']);

                    if (count($levels) === 2)
                        task(new IntercomLevelTask($entranceId, $value['apartment'], trim($levels[0]), trim($levels[1])))->high()->dispatch();
                }
            }

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function DELETE($params)
        {
            $households = backend("households");

            $success = $households->deleteFlat($params["_id"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function index()
        {
            return [
                'GET' => '#same(addresses,house,GET)',
                "POST" => "#same(addresses,house,PUT)",
                "PUT" => "#same(addresses,house,PUT)",
                "DELETE" => "#same(addresses,house,PUT)",
            ];
        }
    }
}