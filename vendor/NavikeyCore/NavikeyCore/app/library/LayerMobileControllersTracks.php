<?php
/**
 * Created by PhpStorm.
 * User: george
 * Date: 25.10.17
 * Time: 15:36
 */

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Map;

class LayerMobileControllersTracks extends Layer
{
    public function __construct(string $dbname, string $collection) {
        $this->model_name = "MobileControllersTracks";
        Layer::__construct($dbname, $collection);
        $this->type = "MobileControllersTracks";
    }

    public function checkNames(Map &$arg, array &$query, array &$options): void{
        if ($arg->hasKey("names")){
            $names = $arg['names'];
            $names=json_decode($names);
            $query['properties.owner'] = ['$in'=>$names];
        }
    }

    public function checkTimes(Map &$arg, array &$query, array &$options): void{
        if (($arg->hasKey("timeA"))||($arg->hasKey("timeB"))){
            $query['properties.day'] = [];
            if ($arg->hasKey("timeA")){
                $timeA=strtotime($arg["timeA"]);
                $query['properties.day']['$gte']=$timeA;
            }
            if ($arg->hasKey("timeB")){
                $timeB=strtotime($arg["timeB"]);
                $query['properties.day']['$lte']=$timeB;
            }
        }
    }

    public function checkBukrs(Map &$arg, array &$query, array &$options): void{
        if ($arg->hasKey("bukrs")){
            $bukrs = $arg['bukrs'];
            $bukrs=json_decode($bukrs);
            $query['properties.bukrs'] = ['$in'=>$bukrs];
        }
    }

    public function getCursor(Map &$arg): Cursor {
        $query = [];
        $options = ["limit"=>7000];
        $this->checkNear($arg, $query, $options);
        $this->checkTimes($arg, $query, $options);
        $this->checkNames($arg, $query, $options);
        $this->checkBukrs($arg, $query, $options);
        return $this->model->find($query, $options);
    }
}