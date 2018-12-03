<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use NavikeyCore\Library\Entitys\HandlerEntity;

class Menu {

    private $handler_entity, $collection;

    public function __construct($dbname, $iconsDir, $path_sort_legend, $path_sort_legend_univers) {
        $this->handler_entity = new HandlerEntity($dbname, $iconsDir, $path_sort_legend, $path_sort_legend_univers);
        $manager = new Manager();
        $this->collection = new Collection($manager, $dbname, "entety_menu");
        unset($manager);
    }

    public function __destruct() {
        unset($this->handler_entity, $this->collection);
    }

    public function findMenu($field, $value_field) {
        $menu_entety = $this->collection->find(["properties.$field" => $value_field]);
        $menu_entety->setTypeMap(['root' => 'array', 'document' => 'array']);
        $menus = [];
        foreach ($menu_entety as $item) {
            array_push($menus, $item["properties"]);
        }
        return $menus;
    }

    public function getMenu($menu_entety, $dpi = 0) {
        $menu = $menu_entety;
        foreach ($menu as &$item) {
            $this->generateMenu($item, $dpi);
        }
        return $menu;
    }

    private function generateMenu(&$entity_menu, $dpi = 0) {
        if (array_key_exists("type", $entity_menu) && !strcmp($entity_menu["type"], "entity")) {
            $properies = [];
            if (array_key_exists("properties", $entity_menu)) {
                $properies = $entity_menu["properties"];
            }
            $properies["class"] = $entity_menu["type"];
            $this->handler_entity->getSubMenu($entity_menu, $properies);
            //$entity_menu["type"] = "layer";
        }
        if (array_key_exists("child", $entity_menu)) {
            foreach ($entity_menu["child"] as &$subtree) {
                $this->generateMenu($subtree, $dpi);
            }
        }
        if (array_key_exists("properties", $entity_menu) &&
                array_key_exists("icon_type", $entity_menu["properties"]) &&
                !strcmp($entity_menu["properties"]["icon_type"], "name")) {
            $entity_menu["properties"]["icon"] = base64_encode(
                    $this->handler_entity->getIocn($entity_menu["properties"], $dpi));
            $entity_menu["properties"]["icon_type"] = "icon";
        }
    }

}
