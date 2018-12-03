<?php

declare(strict_types = 1);

namespace NavikeyCore\Models;

use MongoDB\Driver\Manager;
use MongoDB\BSON\ObjectID;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class LayerModel extends Model {

    public function __construct(string $dbname) {
        parent::__construct($dbname);
        $this->dbname = $dbname;
        $this->manager = new Manager();
    }

    public function __destruct() {
        unset($this->manager);
    }

    public function insert(array $get, string $collectionDbName) {
        $name = "new layer";
        if (array_key_exists("name", $get)) {
            $name = base64_decode($get["name"]);
        }
        if (array_key_exists("userId", $get)) {
            $userId = new ObjectID((string) $get["userId"]);
        } else {
            throw new Exception("Need userId", 400);
        }
        if (array_key_exists("menu", $get)) {
            $menu = new ObjectID((string) $get["menu"]);
        } else {
            throw new Exception("Need menu id", 400);
        }
        $ownerLayerId = $menu;
        if (array_key_exists("ownerlayerid", $get)) {
            $ownerLayerId = new ObjectID((string) $get["ownerlayerid"]);
        }
        $layerId = new ObjectID();
        $this->addLayer($layerId, $userId, $name, $collectionDbName);
        $this->updateMenu($menu, $ownerLayerId, $layerId);
    }

    public function update(array $get) {
        
    }

    private function addLayer(ObjectID $layerId, ObjectID $userId, string $name, string $collectionDbName) {
        $layersCollection = new Collection($this->manager, $this->dbname, "layers");
        $newLayer = [];
        $newLayer["_id"] = $layerId;
        $newLayer["properties"] = ["userId" => $userId,
            "name" => $name, "shared" => "private"];
        $layersCollection->insertOne($newLayer);
        $layersDB = new \Phalcon\Db\Adapter\MongoDB\Database($this->manager, $collectionDbName);
        $layersDB->createCollection((string) $layerId);
        unset($layersCollection, $layersDB);
    }

    private function updateMenu(ObjectID $menuId, ObjectID $ownerLayerId, ObjectID $layerId) {
        $menuCollection = new Collection($this->manager, $this->dbname, "entetyMenu");
        $menu = \MongoDB\BSON\toPHP(\MongoDB\BSON\fromPHP($menuCollection->findOne(["_id" => $menuId])), ['root' => 'array', 'document' => 'array']);
        $this->addMenu($menu, $ownerLayerId, $layerId);
        $menuCollection->updateOne(["_id" => $menu["_id"]], ['$set' => $menu], ["upsert" => true]);
        unset($menuCollection);
    }

    private function addMenu(&$menu, ObjectID $ownerLayerId, ObjectID $layerId) {
        if (!strcmp((string) $menu["_id"], (string) $ownerLayerId)) {
            if (!isset($menu["child"])) {
                $menu["child"] = [];
            }
            array_push($menu["child"], ["_id" => $layerId]);
        }
        if (!isset($menu["child"])) {
            return;
        }
        foreach ($menu["child"] as $item) {
            $this->addMenu($item, $ownerLayerId, $layerId);
        }
    }

}
