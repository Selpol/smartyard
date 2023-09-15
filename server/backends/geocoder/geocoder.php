<?php

namespace backends\geocoder;

use backends\backend;

abstract class geocoder extends backend
{

    /**
     * search for geo objects
     *
     * @param $search
     * @return false|array
     */

    public abstract function suggestions($search);
}
