<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

use function MongoDB\BSON\toJSON;
use MongoDB\Driver\Cursor;
use Ds\Vector;
use Ds\Map;
use NavikeyCore\Library\ArgsMaker;

class LayerLines extends LayerElectric
{

    private $opory, $argsMaker;

    function __construct(string $dbname, string $collection)
    {
        $this->model_name = "Lines";
        $this->type = "LN";
        Layer::__construct($dbname, $collection);
        $this->opory = new LayerElectric($dbname, "Opory");
        $this->argsMaker = new ArgsMaker();
    }

    public function getInfo(Vector &$info, Map &$arg, Array $getargs = []): void
    {
        $cursor = $this->getCursor($arg);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        //$arr=$cursor->toArray();
        //$info->push(...$cursor);
        $info->push(...$cursor->toArray());
        $type = $this->type;
        if ($arg->hasKey("no_opory") && $arg["no_opory"] == 1) {
            $info->apply(function ($document) use ($type) {
                $document["properties"]["type"] = $type;
                return $document;
            });
            return;
        }
        $oporys = $this->opory->getCursor($arg);
        $oporys->setTypeMap(['root' => 'array', 'document' => 'array']);
        $info->push(...$oporys->toArray());
        $info->apply(function ($document) use ($type) {
            $document["properties"]["type"] = $type;
            return $document;
        });
    }

    private function checkTplnr(Map &$arg, array &$query, array &$options)
    {
        if ($arg->hasKey("tplnr")) {
            $arr = json_decode($arg['tplnr']);
            if ($arr == null)
                $query['properties.tplnr'] = $arg['tplnr'];
            else {
                $query['properties.tplnr'] = ['$in' => $arr];
            }
        }
    }

    public function getCursor(Map &$arg): Cursor
    {
        $query = [];
        //$options = ['limit'=>3000];
        $options = [];
        if ($arg->hasKey("limit")) {
            $options["limit"] = (int)$arg["limit"];
        }
        $this->checkNear($arg, $query, $options);

        if ($arg->hasKey("type_line")) {
            if ($arg["type_line"] === '') {
                $query["properties.type"] = ['$exists' => false];
            } else {
                $query["properties.type"] = $arg["type_line"];
            }
        }

        /*if ($arg->hasKey("tplnr")) {
            $query["properties.tplnr"] = $arg["tplnr"];
        }*/
        $this->checkTplnr($arg, $query, $options);
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

        $this->argsMaker->addAbstractArg($arg, $query, "location", "properties.location");
        return $this->model->find($query, $options);
    }
}
