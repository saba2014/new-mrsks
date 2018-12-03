<?php
/**
 * Created by PhpStorm.
 * User: george
 * Date: 28.06.18
 * Time: 12:26
 */

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Map;
use Ds\Vector;
use MongoDB\Driver\Manager;
use NavikeyCore\Library\ArgsMaker;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class LayerUniverseWays extends Layer
{
    private $argsMaker, $db;

    function __construct(string $dbname, string $collection) {
        $this->model_name = "UniverseWays";
        $this->argsMaker = new ArgsMaker();
        $this->db = $dbname;
        Layer::__construct($dbname, $collection);
    }


    private function findElectricObjects($query, $options){
        $objs = new Vector();
       // $answer=[];
        $items = [];
        $items[0] = new LayerPs($this->db, 'Ps');
        $items[1] = new LayerElectric($this->db, 'Opory');
        $items[2] = new LayerLines($this->db, 'Lines');
        foreach ($items as $item){
            $answer = new Vector();
            $cursor = $item->executeQuery($query, $options);
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
            $answer->push(...$cursor->toArray());
            $type = $item->type;
            $answer->apply(function ($document) use ($type) {
                $document["properties"]["type"] = $type;
                return $document;
            });
            $objs->push(...$answer);
        }
        return $objs;
    }


    public function getObjs(Map &$arg, $query){
        if ($arg->hasKey("objects")){
            if ($arg["objects"]==1) {
              //  $this->getObjs($arg);
                $options = [];
                $manager = new Manager();
                $ways = new Collection($manager, $this->db, $this->model_name);
                $way = $ways->findOne($query);
                $tplnrs = $way->properties->tplnrs;
                if (count($tplnrs)===0) return false;
                $newQuery=[
                    "properties.tplnr"=>[
                        '$in'=> $tplnrs
                    ]
                ];
                $this->checkNear($arg, $newQuery, $options);
                return $this->findElectricObjects($newQuery, $options);
            }
            else return false;
        }
        else return false;

    }

    public function getInfo(Vector &$info, Map &$arg, Array $getargs = []): void
    {
        if ($arg->hasKey("objects")){
            $query = [];
            $options = [];
            $this->argsMaker->addId($arg, $query, "id");
            $objs = $this->getObjs($arg, $query);
            if ($objs!=false)
                $info = $objs;
        }
        else {
            $cursor = $this->getCursor($arg);
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
            //$arr=$cursor->toArray();
            //$info->push(...$cursor);
            $info->push(...$cursor->toArray());
            //$type = $this->type;
            /*if ($arg->hasKey("no_opory") && $arg["no_opory"] == 1) {
                $info->apply(function ($document) use ($type) {
                    $document["properties"]["type"] = $type;
                    return $document;
                });
                return;
            }*/
            /*$oporys = $this->opory->getCursor($arg);
            $oporys->setTypeMap(['root' => 'array', 'document' => 'array']);
            $info->push(...$oporys->toArray());*/
            /*$info->apply(function ($document) use ($type) {
                $document["properties"]["type"] = $type;
                return $document;
            });*/
        }
    }

    public function getCursor(Map &$arg): Cursor {
        $query = [];
        $options = [];
        $this->argsMaker->addId($arg, $query, "id");
        $this->checkNear($arg, $query, $options);
        $res = $this->model->find($query, $options);
        return $res;
    }
}