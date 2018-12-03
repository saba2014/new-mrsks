<?php

declare(strict_types = 1);

namespace NavikeyCore\Models;

use \Phalcon\Db\Adapter\MongoDB\Collection;
use \MongoDB\Driver\Manager;

class MenuModel extends Model {

    public function __construct(string $dbname) {
        $this->dbname = $dbname;
        $this->manager = new Manager();
    }

    public function __destruct() {
        unset($this->manager);
    }

    public function insert(array $get) {
        if (array_key_exists("userId", $get)) {
            $userId = $get["userId"];
        } else {
            throw new Exception("Need userId", 400);
        }
        $collection = new Collection($this->manager, $this->dbname, "EntetyMenu");
        $newMenu = [];
        $newMenu["properties"] = ["shared" => "private", "ownerId" => $userId];
        $collection->insert($newMenu);
        unset($collection);
    }

    public function update(array $get) {
        
    }

}
