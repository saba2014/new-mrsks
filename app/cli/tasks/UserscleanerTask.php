<?php
/**
 * Created by PhpStorm.
 * User: george
 * Date: 23.06.18
 * Time: 14:52
 */

use \Phalcon\Cli\Task as Task;
use \MongoDB\Driver\Manager as Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class UserscleanerTask extends Task
{
    private $db;

    public function mainAction(){
        $this->db = $this->config->database->dbname;
        $this->findUnconfirmedUsers();
    }

    public function findUnconfirmedUsers(){
        $manager = new Manager();
        $coll = new Collection($manager,$this->db, "users");
        $period = time() - 24*3600;
        $coll->deleteMany(['$and'=>[
            ['confirm'=>['$exists'=>true]],
            ['time'=>['$lt'=>$period]]
        ]
        ]);

    }

}