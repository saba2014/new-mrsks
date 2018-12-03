<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Map;

class LayerStaff extends Layer
{

    function __construct(string $dbname, string $collection)
    {
        Layer::__construct($dbname, $collection);
    }

    public function getCursor(Map &$arg): Cursor
    {
        $query = [];
        $options = [];
        if ($arg->hasKey("limit")) {
            $options["limit"] = (int)$arg["limit"];
        }
        $this->checkNear($arg, $query, $options);
        if ($arg->hasKey("resId") && ($arg["resId"] !== "")) {
            $query["resId"] = $arg["resId"];
        }
        if ($arg->hasKey("poId") && ($arg["poId"] !== "")) {
            $query["poId"] = $arg["poId"];
        }
        if ($arg->hasKey("walk") && ($arg["walk"] !== "")) {
            $query["walk"] = (bool)$arg["walk"];
        }
        if ($arg->hasKey("typeStaff") && ($arg["typeStaff"] !== "")) {
            $query["typeStaff"] = $arg["typeStaff"];
        }
        return $this->model->find($query, $options);
    }

}