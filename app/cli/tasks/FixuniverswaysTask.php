<?php
/**
 * Created by PhpStorm.
 * User: george
 * Date: 03.07.18
 * Time: 13:27
 */
use \Phalcon\Cli\Task as Task;
use MongoDB\Driver\Manager as Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;


class FixuniverswaysTask extends Task{

    private $collName;

    public function mainAction(array $arg)
    {
        if (isset($arg[0]))
            $this->collName = $arg;
        else $this->collName = "UniverseWays";
        $this->rewriteCollection();
    }

    private function rewriteCollection(){
        $manager = new Manager();
        $waysColl = new Collection($manager, $this->config->database->dbname, $this->collName);
        $ways = $waysColl->find([]);
        $waysColl->deleteMany([]);
        foreach ($ways as $key => $way){
            if (isset($way->properties->icons))
               unset($way->properties->icons);
            unset($way->_id);
            $way->properties->tplnrs = [];
            $waysColl->insertOne($way);
        }
    }

}