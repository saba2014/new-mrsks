<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Vector;
use Ds\Map;
use NavikeyCore\Library\ArgsMaker;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use \MongoDB\Driver\Manager;
use MongoDB\BSON\Regex as Regex;

class LayerWorkers extends Layer {

    private $argsMaker;

    public $manager, $fillColl;

    function __construct(string $dbname, string $collection) {
        $this->model_name = "Collection";
        $this->argsMaker = new ArgsMaker();
        $this->manager = new Manager();
        $this->filColl= new Collection($this->manager, $dbname, "Filiations");
        Layer::__construct($dbname, $collection);
    }
    
    public function getInfo(Vector &$info, Map &$arg): void {       
        if($arg->hasKey("worker")){
            $worker = $arg["worker"];
            $cursor = $this->getCursorWorker($worker, $arg);
        } else {
            $cursor = $this->getCursor($arg);
        }
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        $document = $cursor->toArray();
        for($i = 0; $i < count($document); $i++){
            if(isset($document[$i]["properties"]) && isset($document[$i]["properties"]["time"])){
                $document[$i]["properties"]["time"] = 
                        date("Y-m-d H:i:s", strtotime($document[$i]["properties"]["time"]));
            }
        }
        
        $info->push(...$document);
    }
    
    public function getCursorWorker($worker, Map &$arg): Cursor {
        $options = [];
        if ($arg->hasKey("limit")) {
            $options["limit"] = (int)$arg["limit"];
        }
        $query = ['properties.id' => $worker];
        if ($arg->hasKey("regex") && $arg->hasKey("fieldRegex")) {
            $query[$arg["fieldRegex"]] = ['$regex' => "^{$arg["regex"]}"];
        }
        if ($arg->hasKey("type_worker")) {
            $query["properties.type"] = $arg["type_worker"];
        }
        return $this->model->find($query, ["sort" => [ "properties.registration" => -1 ]],$options);
    }

    public function getCursorWorkerDeviceId($worker, Map &$arg): Cursor {
        $options = [];
        $query = ['properties.deviceId' => $worker];
        if ($arg->hasKey("regex") && $arg->hasKey("fieldRegex")) {
            $query[$arg["fieldRegex"]] = ['$regex' => "^{$arg["regex"]}"];
        }
        if ($arg->hasKey("type_worker")) {
            $query["properties.type"] = $arg["type_worker"];
        }
        return $this->model->find($query, ["sort" => [ "properties.registration" => -1 ]],$options);
    }

    private function workerNumberSearch(&$arg,&$query){
        if ($arg->hasKey("worker_number")){
            $number = $arg["worker_number"];
            $query['$or']=[];
            //$number = $this->argsMaker->isContainStar($number);

            $pos = strpos($number, '*');
            if ($pos !== false) {
                $newNumber="";
                if ($pos+1<strlen($number)) {
                    $newNumber = str_replace("*", '', $number);
                    $newNumber = $newNumber."$";
                    $newNumber = new Regex($newNumber,'i');
                }
                else {
                    $newNumber = new Regex('^'.str_replace("*", '', $number),'i');
                }
                $query['$or'][] = ["properties.number"=>$newNumber];
                $query['$or'][] = ["properties.name"=>$newNumber];
                $number = str_replace("*", '', $number);
            } else {
                $query['$or'][]=["properties.number"=>$number];
                $query['$or'][]=["properties.name"=>$number];
            }

            $query['$or'][]=[
                'properties.hrefs'=>['$elemMatch'=>['name'=>['$regex'=>$number]]]
            ];
            $query['$or'][]=[
                "properties.info"=>['$regex'=>$number]
            ];
        }
    }

    public function getCursor(Map &$arg): Cursor {
        $query = [];
        $options = [];
        //$options = ["limit"=>7000];
        if ($arg->hasKey("limit")) {
            $options["limit"] = (int)$arg["limit"];
        }
        if ($arg->hasKey("type_worker")) {
            $query["properties.type"] = $arg["type_worker"];
        }
        $this->argsMaker->addFilialArg($arg, $query, "filId", "properties.filId",$this->filColl);
       /* $this->argsMaker->severalFieldsQuery($arg, $query, "worker_number",
            ["properties.number","properties.name","properties.info"]);*/
        $this->workerNumberSearch($arg,$query);
        $this->argsMaker->addAbstractArg($arg, $query, "name","properties.name");
        $this->argsMaker->addAbstractArg($arg,$query, "info", "properties.info");
        $this->argsMaker->addAbstractArg($arg, $query, "id", "properties.id");
        $this->checkNear($arg, $query, $options);
        return $this->model->find($query, ["sort" => [ "properties.registration" => -1 ]],$options);
    }
}
