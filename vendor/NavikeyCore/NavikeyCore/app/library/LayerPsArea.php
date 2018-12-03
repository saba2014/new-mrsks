<?php
/**
 * Created by PhpStorm.
 * User: george
 * Date: 06.12.17
 * Time: 12:52
 */

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Map;

class LayerPsArea extends LayerElectric
{

    function __construct(string $dbname, string $collection)
    {
        $this->model_name = "PSArea";
        Layer::__construct($dbname, $collection);
        $this->type = "PSArea";
    }

    public function getCursor(Map &$arg, Array $getargs = []): Cursor
    {
        $query = [];
        //$options = ["limit"=>7000];
        $options = [];
        $this->checkNear($arg, $query, $options);
        if ($arg->hasKey("voltage")) {
            $query["properties.Voltage"] = (float)$arg["voltage"];
        }
        if ($arg->hasKey("ps")) {
            $query["properties.TypeByTplnr"] = $arg["ps"];
        }
        if ($arg->hasKey("tplnr")) {
            $query["properties.tplnr"] = $arg["tplnr"];
        }
        return $this->model->find($query, $options);
    }

}