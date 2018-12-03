<?php

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Map;
use NavikeyCore\Library\TrackController as TrackControllers;

class LayerMobileControllers extends Layer
{
    function __construct(string $dbname, string $collection)
    {
        $this->model_name = "MobileControllers";
        Layer::__construct($dbname, $collection);
        $this->type = "MobileControllers";
    }

    public function getNames(array $query = [], array $options = [])
    {
        return $this->model->distinct("properties.name", $query, $options);
    }

    public function checkNames(Map &$arg, array &$query, array &$options): void
    {
        if ($arg->hasKey("names")) {
            $names = $arg['names'];
            $names = json_decode($names);
            $query['properties.name'] = ['$in' => $names];
        }
    }

    public function checkBukrs(Map &$arg, array &$query, array &$options): void
    {
        if ($arg->hasKey("bukrs")) {
            $bukrs = $arg['bukrs'];
            $bukrs = json_decode($bukrs);
            $query['properties.bukrs'] = ['$in' => $bukrs];
        }
    }

    public function getCursor(Map &$arg): Cursor
    {
        $query = [];
        //$options = ["limit" => 7000];
        $options = [];
        if ($arg->hasKey("limit")) {
            $options["limit"] = (int)$arg["limit"];
        }
        $this->checkNear($arg, $query, $options);
        $this->checkNames($arg, $query, $options);
        $this->checkBukrs($arg, $query, $options);
        return $this->model->find($query, $options);
    }
}