<?php

namespace NavikeyCore\Library;

use NavikeyCore\Library\ArgsMaker;
use MongoDB\Driver\Cursor;
use Ds\Map;

class LayerEmergencyReserve extends Layer
{
    private $argsmaker;

    function __construct(string $dbname, string $collection) {
        $this->model_name = "Collection";
        $this->argsmaker = new ArgsMaker();
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
        if ($arg->hasKey("res_id") && ($arg["res_id"] !== "")) {
            $query["properties.resId"] = $arg["res_id"];
            $query["properties.type"] = "res";
        }
        if ($arg->hasKey("po_id") && ($arg["po_id"] !== "")) {
            $query["properties.poId"] = $arg["po_id"];
            $query["properties.type"] = "po";
        }
        if ($arg->hasKey("fil_id") && ($arg["fil_id"] !== "")) {
            $query["properties.filiationId"] = $arg["fil_id"];
            $query["properties.type"] = "filiation";
        }
        if ($arg->hasKey("res_type") && ($arg["res_type"] !== "")) {
            $query["properties.type"] = $arg["res_type"];
        }
        return $this->model->find($query, $options);
    }

}