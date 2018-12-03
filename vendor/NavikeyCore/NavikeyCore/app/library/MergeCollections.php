<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use Phalcon\Logger\Adapter\File as FileAdapter;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class MergeCollections {

    private $db, $color, $logger;

    public function __construct(string $db, string $path, FileAdapter &$logger) {
        $this->db = $db;
        $this->path = $path;
        $xml = simplexml_load_file($this->path);
        $this->color = [];
        foreach ($xml->color as $color) {
            $this->color[(string) $color->attributes()['voltage']] = (string) $color->attributes()['color'];
        }
        $this->logger = $logger;
    }

    public function Merge(string $new_ps, string $new_line, string $tplnr_list, string $json_list): void {
        $manager = new Manager();
        $tplnrs = new Collection($manager, $this->db, $tplnr_list);
        $json = new Collection($manager, $this->db, $json_list);
        $tplnr_objs = $tplnrs->find();
        $bulk_ps = new BulkWrite();
        $bulk_line = new BulkWrite();
        $this->tplnrUpdate($tplnr_objs, $manager, $bulk_ps, $bulk_line);
        $json_objs = $json->find();
        $this->jsonUpdate($json_objs, $bulk_line);
        try {
            if ($bulk_ps->count()) {
                $manager->executeBulkWrite("{$this->db}.$new_ps", $bulk_ps);
            }
            if ($bulk_line->count()) {
                $manager->executeBulkWrite("{$this->db}.$new_line", $bulk_line);
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $this->logger->error("Ошибка записи в базу: {$e->getMessage()}\n Код ошибки: {$e->getCode()}");
        }
        unset($manager, $tplnrs, $json, $bulk_ps, $bulk_line);
    }

    private function tplnrUpdate(&$tplnr_objs, Manager &$manager, BulkWrite &$bulk_ps, BulkWrite &$bulk_line) {
        $collections = new \Ds\Map();
        foreach ($tplnr_objs as $tplnr_obj) {
            $collection = $tplnr_obj["properties"]["collection"];
            if (!$collections->hasKey($collection)) {
                $collections[$collection] = new Collection($manager, $this->db, $collection);
            }
            $obj = $collections[$collection]->findOne(["properties.tplnr" =>
                $tplnr_obj["properties"]["tplnr"]]);
            if ($obj === null) {
                $this->logger->warning($tplnr_obj["properties"]["tplnr"]);
                continue;
            }
            unset($obj["_id"]);
            if (!strcmp($tplnr_obj["properties"]["collection"], "ps")) {
                $bulk_ps->update(["properties.tplnr" => $tplnr_obj["properties"]["tplnr"]], $obj, ["upsert" => true]);
            } else {
                $bulk_line->update(["properties.tplnr" => $tplnr_obj["properties"]["tplnr"]], $obj, ["upsert" => true]);
            }
        }
        unset($collections);
    }

    private function jsonUpdate(&$json_objs, BulkWrite &$bulk_line) {
        foreach ($json_objs as $json_obj) {
            if (file_exists("../json/" . $json_obj["properties"]["path"])) {
                $json = file_get_contents("../json/" . $json_obj["properties"]["path"]);
                $array = json_decode($json, true);
                $json_obj["properties"]["d_name"] = $json_obj["properties"]["Name"];
                $json_obj["properties"]["Voltage"] = (int) $json_obj["properties"]["Voltage"];
                $json_obj["geometry"] = $array["geometry"];
                $json_obj["properties"]["TypeByTplnr"] = "ЛЭП";
                $json_obj["properties"]["kVoltage"] = $this->color[$json_obj["properties"]["Voltage"]];
                $bulk_line->update(["properties.Name" => $json_obj["properties"]["Name"]], $json_obj, ["upsert" => true]);
            }
        }
    }

}
