<?php

declare(strict_types = 1);

namespace NavikeyCore\Library\Entitys;

use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class HandlerEntity {

    private $dbname, $icon_path, $masks, $univers_mask, $SVG_image, $territory;

    public function __construct(string $dbname, string $icon_path, string $path_sort_legend, string $path_univers) {
        $this->dbname = $dbname;
        $this->icon_path = $icon_path;
        $this->masks = json_decode(file_get_contents($path_sort_legend), true);
        $this->univers_mask = json_decode(file_get_contents($path_univers), true);
        $this->SVG_image = new \NavikeyCore\Library\SvgImage($dbname, $icon_path);
        $this->territory = new \NavikeyCore\Library\Territory($dbname);
    }

    public function __destruct() {
        unset($this->SVG_image, $this->territory);
    }

    public function getSubMenu(&$entity_menu, $properties = []) {
        $type = $entity_menu["type_entity"];
        if (!strcmp($type, "Ps") || !strcmp($type, "Rp") || !strcmp($type, "Tp") || !strcmp($type, "UniversPs") || !strcmp($type, "UniversRp") || !strcmp($type, "UniversTp")) {
            $entity_menu["child"] = $this->ps($type, $entity_menu["properties"]["icon_size"], $entity_menu["properties"]["type"]);
            return;
        }
        switch ($type) {
            case "UniversObj":
                $entity_menu["properties"] = $this->universObj($type, $properties);
                break;
            case "Res":
                $entity_menu["properties"] = $this->res($entity_menu["name"]);
                break;
            case "Po":
                $entity_menu["child"] = $this->po($entity_menu["name"]);
                break;
            case "Territory":
                $entity_menu["child"] = $this->territory();
                break;
            case "Lines":
                $entity_menu["child"] = $this->lines($type, $properties);
                break;
            case "UniversLines":
                $entity_menu["child"] = $this->lines($type, $properties);
                break;
            case "Workers":
                $entity_menu["properties"] = $this->workers($type, $properties);
                break;
            case "Ztp":
                $entity_menu["properties"] = $this->ztp($type, $properties);
                break;
            default :
                $entity_menu["properties"] = $this->getDefault($type, $properties);
                break;
        }
    }

    public function svgToPng($svg, $dpi, $icon_size) {
        return $this->SVG_image->svgToPng($svg, $dpi, $icon_size);
    }

    public function getIocn($properties, $dpi = 0): string {
        $svg = $this->SVG_image->getImage($properties["icon"], $properties["unic"], $properties["color"]);
        if ($dpi > 0) {
            return $this->svgToPng($svg, $dpi, $properties["icon_size"]);
        }
        return $svg;
    }

    private function getDefault(string $type, $properties) {
        $new_properties = $properties;
        $entity = new EntityLayer($type);
        $new_properties["api"] = $entity->get_api();
        return $new_properties;
    }

    private function universObj(string $type, $properties) {
        $entity = new EntityUniversObjs($type, $properties["type"]);
        $new_properties = [];
        $manager = new Manager();
        $collection = new Collection($manager, $this->dbname, "image");
        unset($manager);
        $document = $collection->findOne(["id" => (int) $properties["type"]]);
        $image = file_get_contents($this->icon_path . $document["path"]);
        $new_properties["class"] = $type;
        $new_properties["api"] = $entity->get_api();
        $new_properties["image"] = base64_encode($image);
        return $new_properties;
    }

    private function ps(string $type, $icon_size, string $type_ps) {
        $children = [];
        $new_type = $type;
        switch ($type) {
            case "Ps":
                $type_image = "circle";
                $mask = $this->masks;
                $type_entety = "Ps";
                $new_type = "ps";
                break;
            case "Tp":
                $type_image = "triangle";
                $mask = $this->masks;
                $type_entety = "Ps";
                $new_type = "tp";
                break;
            case "Rp":
                $type_image = "square";
                $mask = $this->masks;
                $type_entety = "Ps";
                $new_type = "rp";
                break;
            case "UniversPs":
                $type_image = "circle";
                $mask = $this->univers_mask;
                $type_entety = "UniversPs";
                $new_type = "ps";
                break;
            case "UniversTp":
                $type_image = "triangle";
                $mask = $this->univers_mask;
                $type_entety = "UniversPs";
                $new_type = "tp";
                break;
            case "UniversRp":
                $type_image = "square";
                $mask = $this->univers_mask;
                $type_entety = "UniversPs";
                $new_type = "rp";
                break;
        }
        if(!array_key_exists($new_type, $mask)) {
            return $children;
        }
        for ($i = 0; $i < count($mask[$new_type]); $i++) {
            $item = $mask[$new_type][$i];
            $entity = new EntityPs($type_entety, $item["voltage"], $type_ps);
            $new_properties = [];
            $new_properties["name"] = $item["voltage"];
            $new_properties["properties"] = [];
            $new_properties["properties"]["api"] = $entity->get_api();
            $new_properties["properties"]["icon"] = $type_image;
            $new_properties["properties"]["color"] = $item["color"];
            $new_properties["properties"]["unic"] = "ps_{$type_image}_" . (int) $item["voltage"];
            $new_properties["properties"]["icon_size"] = $icon_size;
            $new_properties["properties"]["icon_type"] = "name";
            $new_properties["properties"]["class"] = $type;
            $new_properties["type"] = "layer";
            array_push($children, $new_properties);
        }
        return $children;
    }

    private function lines(string $type, $properties) {
        $type_image = "lines";
        $children = [];
        switch ($type) {
            case "Lines":
                $mask = $this->masks;
                break;
            case "UniversLines":
                $mask = $this->univers_mask;
                break;
        }

        for ($i = 0; $i < count($mask["line"]); $i++) {
            $item = $mask["line"][$i];
            if (strcmp($properties["type_line"], $item["type_line"])) {
                continue;
            }
            $entity = new EntityLines($type, $item["voltage"], $item["type_line"]);
            $new_properties = [];
            $new_properties["name"] = $item["voltage"];
            $new_properties["properties"] = [];
            $new_properties["properties"]["icon"] = $type_image;
            $new_properties["properties"]["color"] = $item["color"];
            $new_properties["properties"]["unic"] = "line_" . $item["voltage"];
            $new_properties["properties"]["icon_size"] = $properties["icon_size"];
            $new_properties["properties"]["icon_type"] = "name";
            $new_properties["properties"]["class"] = $type;
            if ($properties["enable_opory"] == 1) {
                $new_properties["child"] = [];
                $line = [];
                $line["name"] = "Пролёты";
                $line["properties"] = [];
                $line["properties"]["api"] = $entity->get_api();
                $line["properties"]["icon"] = $type_image;
                $line["properties"]["color"] = $item["color"];
                $line["properties"]["unic"] = "line_" . $item["voltage"];
                $line["properties"]["icon_size"] = $properties["icon_size"];
                $line["properties"]["icon_type"] = "name";
                $line["type"] = "layer";
                $line["properties"]["class"] = $type;
                array_push($new_properties["child"], $line);
                $opory_entity = new EntityElectric("opory", $item["voltage"]);
                $opory = [];
                $opory["name"] = "Опоры";
                $opory["properties"] = [];
                $opory["properties"]["api"] = $opory_entity->get_api();
                $opory["type"] = "layer";
                $opory["properties"]["icon"] = "opory";
                $opory["properties"]["color"] = $item["color"];
                $opory["properties"]["unic"] = "opory_" . (int) $item["voltage"];
                $opory["properties"]["icon_size"] = $properties["icon_size"];
                $opory["properties"]["icon_type"] = "name";
                $opory["properties"]["class"] = "opory";
                array_push($new_properties["child"], $opory);
                unset($opory_entity);
            } else {
                $new_properties["properties"]["api"] = $entity->get_api();
            }
            array_push($children, $new_properties);
            unset($entity);
        }
        return $children;
    }

    private function res(string $name) {
        $new_properties = [];
        $res_db = $this->territory->getResName($name);
        $res = new EntityRes("res", $res_db["properties"]["RES_id"]);
        $new_properties["api"] = $res->get_api();
        $new_properties["properties"]["class"] = "res";
        unset($res);
        return $new_properties;
    }

    private function po(string $name) {
        $new_properties = [];
        $po = $this->territory->getPoName($name);
        $res_db = $this->territory->getRes($po["properties"]["composite_id"]);
        $res = new EntityRes("res", "");
        foreach ($res_db as $key => $object) {
            $item = [];
            $item["name"] = $key;
            $item["type"] = "layer";
            $res->arg["res_id"] = $object["RES_id"];
            $item["properties"] = [];
            $item["properties"]["api"] = $res->get_api();
            $item["properties"]["class"] = "po";
            array_push($new_properties, $item);
        }
        return $new_properties;
    }

    private function territory() {
        $filiations = $this->territory->getTerritory();
        $res = new EntityRes("res", "");
        $fil_array = [];
        foreach ($filiations as $filiations_key => $filiations_object) {
            $fil_item["name"] = $filiations_key;
            $po_array = [];
            foreach ($filiations_object["po"] as $po_key => $po_object) {
                $po_item["name"] = $po_key;
                $res_array = [];
                foreach ($po_object["res"] as $res_key => $res_object) {
                    $item = [];
                    $item["name"] = $res_key;
                    $item["type"] = "layer";
                    $res->arg["res_id"] = $res_object["RES_id"];
                    $item["properties"] = [];
                    $item["properties"]["api"] = $res->get_api();
                    $item["properties"]["class"] = "res";
                    array_push($res_array, $item);
                }
                $po_item["child"] = $res_array;
                array_push($po_array, $po_item);
            }
            $fil_item["child"] = $po_array;
            array_push($fil_array, $fil_item);
        }
        return $fil_array;
    }

    private function workers(string $type, $properties) {
        $new_properties = $properties;
        $entity = new EntityWorkers($type, $properties["type"]);
        $new_properties["api"] = $entity->get_api();
        return $new_properties;
    }

    private function ztp(string $type, $properties) {
        $new_properties = $properties;
        $entity = new EntityZtp($type, $properties["year_0"], $properties["year_1"]);
        $new_properties["api"] = $entity->get_api();
        return $new_properties;
    }

}