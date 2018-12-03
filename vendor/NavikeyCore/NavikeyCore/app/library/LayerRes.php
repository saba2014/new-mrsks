<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Map;

class LayerRes extends Layer
{

    function __construct(string $dbname, string $collection)
    {
        $this->model_name = "Collection";
        Layer::__construct($dbname, $collection);
    }

    public function getCursor(Map &$arg): Cursor
    {
        $query = [];
        //$options = ["limit"=>7000];
        $options = [];
        $this->checkNear($arg, $query, $options);
        if ($arg->hasKey("res_id") && ($arg["res_id"] !== "")) {
            $query["properties.RES_id"] = $arg["res_id"];
        }
        return $this->model->find($query, $options);
    }

}
