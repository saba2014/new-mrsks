<?php


use \Phalcon\Cli\Task as Task;
use MongoDB\Driver\Manager as Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use \Phalcon\Logger\Adapter\File as FileAdapter;
use MongoDB\BSON\Regex as Regex;


class RepairworkersTask extends Task
{
    private $idColl, $db, $coll;

    public function mainAction(array $arg)
    {
        if (!isset($arg[0]) || !isset($arg[1])) {
            echo "Need id car and walk\n";
            return;
        }
        $car = $arg[0];
        $walk = $arg[1];
        $idsName = "MobileDivisions";
        $workersName = "Workers";
        $manager = new Manager();
        $this->idColl = new Collection($manager, $this->config->database->dbname, $idsName);
        $this->coll = new Collection($manager, $this->config->database->dbname, $workersName);
        ///$id = $this->findId("5b46ed268873e6702e08d632");
        $this->findAndChange("properties.type", "car", $car);
        // $id = $this->findId("Работники ОВБ");
        $this->findAndChange("properties.type", "walk", $walk);
    }

    private function findId($name)
    {
        $res = $this->idColl->find(["properties.name" => $name]);
        $res = $res->toArray();
        $id = $res[0]->_id;
        return (string)$id;
    }

    private function findAndChange($field, $oldValue, $newValue)
    {
        $this->coll->updateMany([$field => $oldValue], ['$set' => [$field => $newValue]], ['$upsert' => true]);
    }

}