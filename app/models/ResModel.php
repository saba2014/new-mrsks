<?php

declare(strict_types=1);

use \MongoDB\Driver\Manager;
use \MongoDB\BSON\ObjectID;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class ResModel extends NavikeyCore\Models\Model
{

    public function __construct(string $dataBaseName)
    {
        parent::__construct($dataBaseName);
        $this->collection = new \Phalcon\Db\Adapter\MongoDB\Collection($this->manager, $this->dbname, "Res");
    }

    public function __destruct()
    {
        unset($this->manager);
    }

    public function insert(array $get)
    {
    }

    public function update(array $get)
    {

    }

}
