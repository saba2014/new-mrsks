<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

use \MongoDB\Driver\Manager;
use \MongoDB\Driver\Cursor;
use \Ds\Map;
use \Ds\Vector;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use MongoDB\BSON\Regex as Regex;

class Layer
{

    public $collection, $manager, $model, $model_name, $get, $allow;

    public function __construct(string $dbname, string $collection)
    {
        $this->collection = $collection;
        $this->manager = new Manager();
        $this->model = new Collection($this->manager, $dbname, $this->collection);
        $this->allow = new Vector(["Guests", "Users", "Admin", "Master_admin"]);
    }

    public function __destruct()
    {
        unset($this->manager, $this->model);
    }

    public function getInfo(Vector &$info, Map &$arg): void
    {
        $cursor = $this->getCursor($arg);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        $array = $cursor->toArray();
        foreach ($array as $item) {
            if (isset($item["properties"]) && isset($item["properties"]["time"])) {
                $st = (string)$item["properties"]["time"];
                $item["properties"]["time"] = (int)substr($st, 0, strlen($st) - 3);
            }
            $info->push($item);
        }
        //$info->push(...$cursor->toArray());
    }

    public function getGeoQuery(Map &$arg, array &$query): void
    {
        if ($arg->hasKey("geometry")) {
            //var_dump($arg["geometry"][0], $arg["geometry"][1], $arg["geometry"][2], $arg["geometry"][count($arg["geometry"]) - 1]);//exit;
            $query['geometry'] = ['$geoIntersects' => ['$geometry' => ['type' => 'Polygon',
                'coordinates' => [
                    $arg["geometry"]
                ],
            ]]];
            return;
        }

        if (!($arg->hasKey("lon1") && $arg->hasKey("lat1") && $arg->hasKey("lon2") && $arg->hasKey("lat2"))) {
            return;
        }
        $lon1 = $arg["lon1"];
        $lat1 = $arg["lat1"];
        $lon2 = $arg["lon2"];
        $lat2 = $arg["lat2"];
        $query['geometry'] = ['$geoIntersects' => ['$geometry' => ['type' => 'Polygon',
            'coordinates' => [
                [
                    [$lon1, $lat1],
                    [$lon2, $lat1],
                    [$lon2, $lat2],
                    [$lon1, $lat2],
                    [$lon1, $lat1]
                ],
            ], 'crs' => ['type' => 'name', 'properties' => ['name' => 'urn:x-mongodb:crs:strictwinding:EPSG:4326']]
        ]]];
//        $query = array('geometry.coordinates' => array('$geoWithin' => array('$box' => array(array($lon1, $lat1),
//            array($lon2, $lat2)))));
    }

    public function getNearQuery(int $near, Map &$arg, array &$query): void
    {
        if (!($arg->hasKey("lon") && $arg->hasKey("lat"))) {
            return;
        }
        $lon = $arg["lon"];
        $lat = $arg["lat"];
        $query['geometry'] = ['$near' => ['$geometry' =>
            ['type' => 'Point', 'coordinates' =>
                [floatval($lon), floatval($lat)]
            ],
            '$maxDistance' => $near
        ]];
    }

    public function getCursor(Map &$arg): Cursor
    {
        $query = [];
        //$options = ['limit'=>7000];
        $options = [];
        if ($arg->hasKey("limit")) {
            $options["limit"] = (int)$arg["limit"];
        }
        $this->checkNear($arg, $query, $options);
        return $this->model->find($query, $options);
    }

    public function checkNear(Map &$arg, array &$query, array &$options): void
    {
        if ($arg->hasKey("near")) {
            $near = $arg["near"];
            $this->getNearQuery(intval($near), $arg, $query);
        } else {
            $this->getGeoQuery($arg, $query);
        }
        if ($arg->hasKey("regex") && $arg->hasKey("fieldRegex")) {
            $que = new Regex("^" . str_replace('*', "", $arg["regex"]));
            $query[$arg["fieldRegex"]] = ['$regex' => $que, '$options' => 'i'];
        }
        if ($arg->hasKey("skip")) {
            $options["skip"] = (integer)$arg["skip"];
        }
    }

    public function isAllow(string $role): bool
    {
        if ($this->allow->find($role) === false) {
            return false;
        }
        return true;
    }

    public function insertOne(array $properties, array $coordinates, string $type)
    {
        $document = [];
        $document["type"] = "Feature";
        $document["properties"] = $properties;
        $timestamp = new \MongoDB\BSON\UTCDateTime();
        $document["properties"]["time"] = $timestamp;
        $document["geometry"] = [];
        $document["geometry"]["type"] = $type;
        $document["geometry"]["coordinates"] = $coordinates;
        $this->model->insertOne($document);
    }

    public function executeQuery($query, $options = []): Cursor
    {
        return $this->model->find($query, $options);
    }

    public function getQueryItemsCount($query, $options = []): int
    {
        return $this->model->count($query, $options);
    }
}
