<?php

/**
 * configs api
 */

namespace api\configs {

    use api\api;
    use Selpol\Feature\Frs\FrsFeature;

    /**
     * configs method
     */
    class configs extends api
    {
        public static function GET($params)
        {
            $sections = ["FRSServers" => container(FrsFeature::class)->servers()];

            return api::ANSWER($sections, "sections");
        }

        public static function index()
        {
            return [
                "GET" => "#common",
            ];
        }
    }
}