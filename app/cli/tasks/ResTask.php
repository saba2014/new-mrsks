<?php

declare(strict_types=1);

use Phalcon\Cli\Task;
use MongoDB\Driver\Manager as Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use MongoDB\Driver\BulkWrite;
use NavikeyCore\Library\LayerRes;


class ResTask extends Task
{

    private $resCenters;

    public function mainAction()
    {
        $this->resCenters = $this->createColl("ResCenter");
        $res = $this->resCenters->find([]);
        $arr = $res->toArray();
        $this->fixResCenter($arr);
    }

    public function fixResCenter($arr)
    {
        $filColl = $this->createColl("Filiations");
        $poColl = $this->createColl("Po");
        for ($i = 0; $i < count($arr); $i++) {
            $poId = $arr[$i]['properties']['poId'];
            $filId = $arr[$i]['properties']['filiationId'];
            $filName = $this->findFilId($filColl, $filId);
            $poName = $this->findPoId($poColl, $poId);
            $manager = new Manager();
            $bulk = new BulkWrite();
            $bulk->update(['properties.address' => $arr[$i]['properties']['address']], ['$set' => ["properties.filiation" => $filName, "properties.po" => $poName]]);
            $manager->executeBulkWrite('test.ResCenter', $bulk);
        }
    }

    public function createColl($name)
    {
        $manager = new Manager();
        $res = new Collection($manager, $this->config->database->dbname, $name);
        return $res;
    }

    private function findFilId($coll, $id)
    {
        $obj = $this->findInCollection($coll, ["properties.id" => $id]);
        $res = $obj["properties"]["name"];
        return $res;
    }

    private function findPoId($coll, $id)
    {
        $obj = $this->findInCollection($coll, ["properties.composite_id" => $id]);
        $res = $obj["properties"]["name"];
        return $res;
    }

    private function findInCollection($coll, $param)
    {
        $obj = $coll->findOne($param);
        if ($obj)
            return $obj;
        else return false;
    }

    public function insertOneAction($arg)
    {
        $item = $arg[0];
        $file = file_get_contents($item);
        $json = json_decode($file, true);
        $Res = new LayerRes($this->config->database->dbname, 'Res');
        $answer = $Res->model->insert($json);
    }

    public function insertDagestanAction($arg)
    {
        $fileName = $arg[0];
        $json = file_get_contents($fileName);
        $jsonDecoded = json_decode($json, true);
        $manager = new Manager();
        $collection = new Collection($manager, $this->config->database->dbname, 'Ps');
        foreach ($jsonDecoded as &$item) {
            $item['properties']['coordinates'] = true;
            $item['properties']['oTypePS'] = 2;
        }
        $result = $collection->insertMany($jsonDecoded);
        echo "Ok\n";
        return 0;
    }

    public function buildPoPolygonAction()
    {
        $manager = new Manager();
        $poCollection = new Collection($manager, $this->config->database->dbname, 'Po');
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $pos = $poCollection->find([], $options)->toArray();
        $resCollection = new Collection($manager, $this->config->database->dbname, 'Res');
        foreach ($pos as $po) {
            $polygon = $this->findResPolygonsByBranch($po['properties']['composite_id'], $resCollection);
            $po['geometry']['type']='MultiPolygon';
            $po['geometry']['coordinates']=$polygon;
            echo 'debug';
        }
    }

    private function findResPolygonsByBranch($branch, $resCollection)
    {
        $query = ['properties.branch' => $branch];
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $reses = $resCollection->find($query, $options)->toArray();
        $holes = [];
        $points = [];
        foreach ($reses as $res) {
            if (count($res['geometry']['coordinates']) !== 1) {
                for ($i = 1; $i < count($res['geometry']['coordinates']); $i++) {
                    $tempHoles=[];
                    $tempHoles = array_merge($tempHoles, $res['geometry']['coordinates'][$i]);
                    array_push($holes,$tempHoles);
                }
            }
            $points = array_merge($points, $res['geometry']['coordinates'][0][0]);
            // build polygon here
        }
        $answer = ['points' => $points, 'holes' => $holes];
        return $answer;
    }


}