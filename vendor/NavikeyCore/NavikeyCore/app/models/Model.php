<?php

declare(strict_types=1);

namespace NavikeyCore\Models;

class Model
{

    public $dbname, $manager, $collection;

    public function __construct(string $dbname)
    {
        $this->dbname = $dbname;
        $this->manager = new \MongoDB\Driver\Manager();
        $this->collection = new \Phalcon\Db\Adapter\MongoDB\Collection($this->manager, $this->dbname, "Model");
    }

    public function insert(array $get)
    {
        $this->collection->insert($get["object"]);
    }

    public function update(array $get)
    {
        $this->collection->updateOne($get["query"], ['$set' => $get["object"]]);
    }

    public function find(array $get): array
    {
        $cursor = $this->collection->find($get["query"]);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        return $cursor->toArray();
    }

}
