<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Manager;

class Territory {

    private $manager, $po, $filiations, $res;

    public function __construct(string $dbname) {
        $this->manager = new Manager();
        $this->filiations = new \Phalcon\Db\Adapter\MongoDB\Collection($this->manager, $dbname, "Filiations");
        $this->po = new \Phalcon\Db\Adapter\MongoDB\Collection($this->manager, $dbname, "Po");
        $this->res = new \Phalcon\Db\Adapter\MongoDB\Collection($this->manager, $dbname, "Res");
    }

    public function getTerritory(): array {
        $tree = [];
        $filiations = $this->filiations->find();
        $filiations->setTypeMap(['root' => 'array', 'document' => 'array']);
        foreach ($filiations as $filiation) {
            $name = $filiation["properties"]["name"];
            $tree[$name] = [];
            $tree[$name]["self"] = $filiation["properties"];
            $tree[$name]["po"] = $this->getPo($filiation["properties"]["id"]);
        }
        return $tree;
    }

    public function getResName(string $name) {
        return $this->res->findOne(["properties.Label" => $name]);
    }

    public function getPoName(string $name) {
        return $this->po->findOne(["properties.name" => $name]);
    }

    public function getPo(string &$parent_id): array {
        $tree = [];
        $pos = $this->po->find(["properties.branch" => $parent_id]);
        $pos->setTypeMap(['root' => 'array', 'document' => 'array']);
        foreach ($pos as $po) {
            $name = $po["properties"]["name"];
            $tree[$name] = [];
            $tree[$name]["self"] = $po["properties"];
            $tree[$name]["res"] = $this->getRes($po["properties"]["composite_id"]);
        }
        return $tree;
    }

    public function getRes(string &$parent_id): array {
        $tree = [];
        $ress = $this->res->find(["properties.branch" => $parent_id]);
        $ress->setTypeMap(['root' => 'array', 'document' => 'array']);
        foreach ($ress as $res) {
            $name = $res["properties"]["Label"];
            $tree[$name] = $res["properties"];
        }
        return $tree;
    }

}
