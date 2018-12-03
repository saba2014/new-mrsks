<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Map;
use Ds\Vector;

class LayerElectric extends Layer
{

    public $type;

    function __construct(string $dbname, string $collection)
    {
        $this->model_name = "Opory";
        Layer::__construct($dbname, $collection);
        $this->type = "OP";
    }

    public function getCursor(Map &$arg): Cursor
    {
        $query = [];
        $options = [];
        if ($arg->hasKey("limit")) {
            $options["limit"] = (int)$arg["limit"];
        }
        $this->checkNear($arg, $query, $options);
        /*if ($arg->hasKey("voltage")) {
            $query["properties.Voltage"] = $arg["voltage"];
        }*/
        if ($arg->hasKey("voltage")) {
            $arr = json_decode($arg["voltage"]);
            if (!is_array($arr)) {
                $query["properties.Voltage"] = (float)$arg["voltage"];
            } else {
                $this->argsMaker->addAbstractArg($arg, $query, "voltage", "properties.Voltage");
            }
        }
        if ($arg->hasKey("tplnr")) {
            $query["properties.tplnr"] = $arg["tplnr"];
        }
        if ($arg->hasKey("location")) {
            $query["properties.location"] = $arg["location"];
        }
        return $this->model->find($query, $options);
    }

    public function checkForDeepGeoemtry(Vector &$info, Map &$arg, $arr){
        $res = [];
        $type = $arg["geometry_type"];
        for ($j=0;$j<count($arr);$j++) {
            $cursorType = $arr[$j]['geometry']['type'];
            if (!strcmp($cursorType, "GeometryCollection")) {
                for ($i = 0; $i < count($arr[$j]['geometry']['geometries']); $i++) {
                    if (!strcmp($arr[$j]['geometry']['geometries'][$i]['type'], $type)) {
                        $arr[$j]['geometry'] = $arr[$j]['geometry']['geometries'][$i];
                        $res[]=$arr[$j];
                        //$info->push(...$arr);
                        break;
                    }
                }
            }
            else if (!strcmp($cursorType, $type)){
                $res[]=$arr[$j];
            }
        }
        $info->push(...$res);
    }

    public function getInfo(Vector &$info, Map &$arg, Array $getargs = []): void
    {
        $cursor = $this->getCursor($arg, $getargs);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        if ($arg->hasKey("geometry_type"))
        {
            $this->checkForDeepGeoemtry($info, $arg, $cursor->toArray());
        }
        else $info->push(...$cursor->toArray());
        $type = $this->type;
        $info->apply(function ($document) use ($type) {
            $document["properties"]["type"] = $type;
            return $document;
        });
    }
}
