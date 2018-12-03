<?php
declare(strict_types = 1);

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use Phalcon\Cli\Task;

/**
 * Fixed color, voltage in data base
 *
 * @author admin
 */
class TempTask extends Task {

    public function mainAction() {
        
    }
    
    public function listdelAction() {
        
        $manager = new Manager();
        $db = $this->config->database->dbname;
        $model = new BaseCollection($manager, $db, "univers_objs");
        $cursor = $model->find(["properties.info" => ['$exists' => false]]);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        file_put_contents("../public/backup/list_del.txt", "");
        $array = $cursor->toArray();
        file_put_contents("../public/backup/list_del.json", json_encode($array));
        foreach($array as $item){
            //echo $item["properties"]["name"] . "/n";
            file_put_contents("../public/backup/list_del.txt", $item["properties"]["name"] . "\n", FILE_APPEND);
        }
        unset($manager, $model);
    }

    public function hashAction($arg)
    {
        $password = "";
        if(count($arg) && isset($arg[0])) {
            $password = $arg[0];
        }
        echo crypt($password);
        echo "\n";
    }
}
