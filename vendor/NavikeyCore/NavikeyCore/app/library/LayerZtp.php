<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Map;
use NavikeyCore\Library\ArgsMaker;

class LayerZtp extends Layer {

    private $argsMaker;

    function __construct(string $dbname, string $collection) {
        $this->model_name = "Collection";
        $this->argsMaker = new ArgsMaker();
        Layer::__construct($dbname, $collection);
    }

    public function isZatrat(Map &$arg, &$query){
        if ($arg->hasKey("kapzatr")){
            if ($arg["kapzatr"] == 1){
                $query["properties.kapzatr"]="X";
            }
            if ($arg["kapzatr"] == 0){
                $query["properties.kapzatr"]['$exists']=false;
            }
        }
    }

    public function getCursor(Map &$arg): Cursor {
        $query = [];
        $options = ["limit"=>7000];
        if ($arg->hasKey("limit")) {
            $options["limit"] = (int)$arg["limit"];
        }
        $this->checkNear($arg, $query, $options);
        if ($arg->hasKey("year_0")) {
            $year_0 = $arg["year_0"];
        } else {
            $year_0 = 0;
        }
        if ($arg->hasKey("year_1")) {
            $year_1 = $arg["year_1"];
        } else {
            $year_1 = 1000;
        }
        $today = getdate();
        $this->argsMaker->dealWithRegExp($arg,$query);
        $query["properties.date"] = [];
        $query["properties.date"]['$gte'] = ($today["year"] - $year_1) . "-01-01";
        $query["properties.date"]['$lte'] = ($today["year"] - $year_0) . "-12-31";
        $this->isZatrat($arg,$query);
        return $this->model->find($query, $options);
    }

}
