<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Map;
use NavikeyCore\Library\ArgsMaker;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class LayerPs extends LayerElectric
{
    private $argsMaker;

    function __construct(string $dbname, string $collection)
    {
        $this->model_name = "PS";
        Layer::__construct($dbname, $collection);
        $this->type = "PS";
        $this->argsMaker = new ArgsMaker();

    }

    private function checkTplnr(Map &$arg, array &$query, array &$options)
    {
        if ($arg->hasKey("tplnr")) {
            $arg["tplnr"] = strtoupper($arg["tplnr"]);
            $arr = json_decode($arg['tplnr']);
            if ($arr == null) {
                //$query['properties.tplnr'] = $arg['tplnr'];
                $pos = strpos($arg["tplnr"], '*');
                if ($pos == false)
                    $query["properties.tplnr"] = $arg["tplnr"];
                else {
                    $query["properties.tplnr"] = ['$regex' => str_replace("*", "", $arg["tplnr"])];
                }
            } else {
                $query['properties.tplnr'] = ['$in' => $arr];
            }
        }
    }


    public function getCursor(Map &$arg, Array $getargs = []): Cursor
    {
        /**
         * для запроса при перемещении
         */
        $query = [];
        //$options = ["limit"=>7000];
        $options = [];
        if ($arg->hasKey("limit")) {
            $options["limit"] = (int)$arg["limit"];
        }
        $this->checkNear($arg, $query, $options);
        if ($arg->hasKey("voltage")) {
            $arr = json_decode($arg["voltage"]);
            if (!is_array($arr)) {
                $query["properties.Voltage"] = (float)$arg["voltage"];
            } else {
                $this->argsMaker->addAbstractArg($arg, $query, "voltage", "properties.Voltage");
            }
        }
        if ($arg->hasKey("ps")) {
            $query["properties.TypeByTplnr"] = $arg["ps"];
        }
        if ($arg->hasKey("TypeByTplnr")) {
            $query["properties.TypeByTplnr"] = $arg["TypeByTplnr"];
        }
        //$this->checkTplnr($arg, $query,$options);
        if ($arg->hasKey("kl_u")) {
            $query["properties.kl_u"] = $arg["kl_u"];
        }

        //$arg["tplnr"] = strtoupper($arg["tplnr"]);

      //  $options = ['geometry'=>true];
        $this->argsMaker->addAbstractArg($arg, $query, "location", "properties.location");
        $this->argsMaker->addAbstractArg($arg, $query, "balance", "properties.balance");
        $this->argsMaker->addAbstractArg($arg, $query, "tplnr", "properties.tplnr");

        if ($arg->hasKey("balance_name")) {
            $query["properties.balance_name"] = $arg["balance_name"];
        }
        $res = $this->model->find($query, $options);
       // $arr = $res->toArray();
        return $res;
    }
}
