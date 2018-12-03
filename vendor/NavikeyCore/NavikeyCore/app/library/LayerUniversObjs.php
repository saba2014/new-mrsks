<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Map;

class LayerUniversObjs extends Layer {

    function __construct(string $dbname, string $collection) {
        $this->model_name = "Collection";
        Layer::__construct($dbname, $collection);
    }

    public function getCursor(Map &$arg): Cursor {
        $query = [];
        //$options = ["limit"=>7000];
        $options = [];
        if ($arg->hasKey("limit")) {
            $options["limit"] = (int)$arg["limit"];
        }
        $this->checkNear($arg, $query, $options);
        if ($arg->hasKey("type_obj")) {
            $query["properties.type"] = $arg["type_obj"];
        }
        return $this->model->find($query, $options);
    }

}
