<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

use \Ds\Map;
use \MongoDB\Driver\BulkWrite;
use \MongoDB\Driver\Manager;
use \Phalcon\Logger\Adapter\File as FileAdapter;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use NavikeyCore\Library\SRTMGeoTIFFReader as SRTM;

/**
 * Description of Core
 *
 * @author alex
 */
class Lines extends ElectricObjects
{

    private $error_count, $count_lines, $container, $count_sector, $check, $logger, $manager, $bulk_lines,
        $bulk_opory, $bulk_line_bonds, $line_bonds, $db;

    public function __construct(string $path, FileAdapter $logger, string $db)
    {
        $this->error_count = 0;
        $this->check = new CheckTplnr($path);
        $this->count_lines = 0;
        $this->count_sector = 0;
        $this->container = [];
        $this->logger = $logger;
        $this->manager = new Manager();
        $this->bulk_lines = new BulkWrite();
        $this->bulk_opory = new BulkWrite();
        $this->bulk_line_bonds = new BulkWrite();
        $this->db = $db;
        $this->line_bonds = new Collection($this->manager, $db, "LineBonds");
    }

    public function load(\SimpleXMLElement $xmllines, Map &$points)
    {
        $this->logger->log("<b>Всего линий: " . count($xmllines) . "<br /></b><ul>\n");
        foreach ($xmllines as $line) {
            if (!isset($line->attributes()["tplnr"])) {
                $this->logger->log("<li> У линии " . $line->attributes()["d_name"] . "("
                    . ") отсутсвует идентификатор TPLNR!</li>\n");
                continue;
            }
            $data = [];
            $this->dataAddMainInfo($data, $line);
            $this->dataAddStatus($data, $line);
            $this->dataAddAddition($data, $line, $points);
            $tplnr = $this->dataAddTplnr($data, $line);
            $this->lineLoad($data, $line, $points, $tplnr);
        }
        try {
            if ($this->bulk_lines->count()) {
                $this->manager->executeBulkWrite("$this->db.Lines", $this->bulk_lines);
            }
            if ($this->bulk_opory->count()) {
                $this->manager->executeBulkWrite("$this->db.Opory", $this->bulk_opory);
            }
            if ($this->bulk_line_bonds->count()) {
                $this->manager->executeBulkWrite("$this->db.LineBonds", $this->bulk_line_bonds);
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
//            echo "<li>     Ошибка записи в базу: " . $e->getMessage() . "<br> Код ошибки: " .
//                    $e->getCode() . "</li>";
            $this->logger->error("<li>     Ошибка записи в базу: " . $e->getMessage() . "<br> Код ошибки: " .
                $e->getCode() . "</li>");
        }
        $this->logger->log("</ul> $this->error_count ошибок в линиях.<hr/>\n");
    }


    /*
     * function which check distance between 2 opory and compare with maximum
     */
    private function dataIsCorrect(Map $points, Line &$new_line, \SimpleXMLElement $line, string $tplnr): bool
    {
        $mask = $this->check->getMask($tplnr);
        $srtm = new SRTMGeoTIFFReader('');
        if ($mask->limit) {
            $key_1 = (string)$line->point[0]->attributes()["point_code"];
            $key_2 = (string)$line->point[1]->attributes()["point_code"];
            $new_line->key = $key_1;
            if ($this->checkKey($points, $new_line, $tplnr)) {
                return false;
            }
            $new_line->key = $key_2;
            if ($this->checkKey($points, $new_line, $tplnr)) {
                return false;
            }
            $dist = 1000 * $srtm->getDistance(
                    $points[$key_1]["coords"][1], $points[$key_1]["coords"][0],
                    $points[$key_2]["coords"][1], $points[$key_2]["coords"][0], false);
            unset($srtm);
            if ($dist > $mask->limit) {
                $this->logger->info("<li>Ошибка в линии: " . $tplnr . " расстояние между опорами больше максимального $key_1 и $key_2 максимальное: {$mask->limit}, текущее:
$dist</li>\n");
                $new_line->error_line = false;
                $this->error_count++;
                return false;
            }
            return true;
        }
        return true;
    }

    private function dataAddMainInfo(&$data, \SimpleXMLElement $line): void
    {
        $data["type"] = "Feature";
        $data["properties"] = [];
        $data["properties"]["d_name"] = (string)$line->attributes()["d_name"];
        if (isset($line->balance)) {
            $data["properties"]["balance"] = (string)$line->balance->attributes()["code"];
            $data["properties"]["balance_name"] = (string)$line->balance->attributes()["name"];
        }
        if (isset($line->attributes()["location"])) {
            $data["properties"]["location"] = (string)$line->attributes()["location"];
        }
        $data["properties"]["TypeByTplnr"] = '';
        $data["geometry"] = [];
        $data["geometry"]["type"] = "LineString";
        $data["geometry"]["coordinates"] = [];
    }

    private function dataAddStatus($data, \SimpleXMLElement $line): void
    {
        if (isset($line->sysstat)) {
            $sysstat = [];
            foreach ($line->sysstat as $stat) {
                $sysstat[] = (string)$stat->attributes()["name"];
            }
            $data["properties"]["sysstat"] = $sysstat;
        }
        if (isset($line->usrstat)) {
            $usrstat = [];
            foreach ($line->usrstat as $stat) {
                $usrstat[] = (string)$stat->attributes()["name"];
            }
            $data["properties"]["usrstat"] = $usrstat;
        }
    }

    private function dataAddAddition(&$data, \SimpleXMLElement $line, Map $points): void
    {
        $addition = [];
        $this->dataAddAdditionWires($addition, $line, $points);

        if (count($line->max_amperage)) {
            $addition["max_amperage"] = (double)$line->max_amperage->attributes()["value"];
        }
        if (count($line->specifications)) {
            $addition["specifications"] = (double)$line->specifications->attributes()["amount"];
        }
        if (isset($line->contracts)) {
            $addition["contracts"] = (double)$line->contracts->attributes()["amount"];
        }
        if (isset($line->root)) {
            $addition["root"] = (string)$line->root->attributes()["name"];
            $addition["root_tplnr"] = (string)$line->root->attributes()["tplnr"];
        }
        if (isset($line->root_switchgear)) {
            $addition["root_switchgear"] = (string)$line->root_switchgear->attributes()["name"];
        }
        $data["properties"]["addition"] = $addition;
    }

    private function dataAddAdditionWires(&$addition, \SimpleXMLElement $line, Map $points): void
    {
        $addition['wires'] = [];
        $pr_parts = $line->pr_part;
        for ($i = 0; $i < count($pr_parts); $i++) {
            $temp_begin = (string)$pr_parts[$i]->attributes()["p_begin"];
            if (isset($temp_begin) && isset($points[$temp_begin]) && isset($points[$temp_begin]["name"])) {
                $wire["p_begin"] = $points[$temp_begin]["name"];
            }
            $temp_end = (string)$pr_parts[$i]->attributes()["p_end"];
            if (isset($temp_end) && isset($points[$temp_end]) && isset($points[$temp_end]["name"])) {
                $wire["p_end"] = $points[$temp_end]["name"];
            }
            $wire["length"] = (double)$pr_parts[$i]->attributes()["length"];
            $wire["marka"] = $pr_parts[$i]->attributes()["marka"];
            $addition['wires'][] = $wire;
        }
    }

    private function setColor(&$data, string $tplnr): void
    {
        $mask = $this->check->getMask($tplnr);
        $data["properties"]["tplnr"] = $tplnr;
        $data["properties"]["kVoltage"] = $mask->color;
        $data["properties"]["Voltage"] = $mask->voltage;
    }

    private function dataAddTplnr(&$data, \SimpleXMLElement $line): string
    {
        $tplnr = $this->dataGetTplnr($data, $line);
        $this->setColor($data, $tplnr);
        return $tplnr;
    }

    private function dataGetTplnr(&$data, \SimpleXMLElement $line): string
    {
        $tplnr = mb_strtoupper((string)$line->attributes()["tplnr"], 'UTF-8');
        $type = $this->check->getType($tplnr);
        if ($type) {
            $data["properties"]["TypeByTplnr"] = $type;
            $data["properties"]["type"] = $this->check->getTypeLine($tplnr);
            if ((strcmp($type, "ЛЭП")) && (strcmp($type, "Участок")) && (strcmp($type, "Отпайка"))) {
                $this->logger->log("<li>Указан не тот тип для ЛЭП TPLNR = " . $tplnr . " тип = " . $type . "</li>");
                $this->error_count++;
            }
        } else {
            $this->logger->log("<li>TPLNR = " . $tplnr . " не найдено </li> ");
            $this->error_count++;
        }
        return $tplnr;
    }

    private function lineLoad($data, \SimpleXMLElement $line, Map &$points, string $tplnr): void
    {
        if (count($line->point) > 1) {
            $new_line = new Line();
            $this->lineCoordGet($data, $line, $tplnr, $points, $new_line);
            $new_line->load($this->bulk_lines);
        } else {
            $this->lineError($line);
        }
    }

    private function lineCoordGet($data, \SimpleXMLElement $line, string $tplnr, Map &$points, Line &$new_line): void
    {
        $new_line->data = $data;
        if (!isset($new_line->data["geometry"])) {
            $new_line->data["geometry"] = [];
        }
        $new_line->tplnr = $tplnr;
        if ($this->checkDublicate($points, $new_line, $line, $tplnr)) {
            return;
        }
        if (!$this->dataIsCorrect($points, $new_line, $line, $tplnr)) {
            return;
        }

        for ($i = 0; $i < count($line->point); $i++) {
            $new_line->key = (string)$line->point[$i]->attributes()["point_code"];
            if ($this->checkKey($points, $new_line, $tplnr)) {
                break;
            }
            $new_line->data["geometry"]["coordinates"][] = $points[$new_line->key]["coords"];
            if (strripos($new_line->key, $tplnr) !== false) {
                if (isset($data["properties"]["balance_name"])) {
                    $points[$new_line->key]["data"]["properties"]["balance"] = $data["properties"]["balance_name"];
                }
                $this->bulk_opory->update(["properties.tplnr" => $new_line->key], $points[$new_line->key]["data"]->toArray(), ["upsert" => true]);
            }
            if ((!$this->line_bonds->findOne(["opory_tplnr" => $new_line->key, "line_tplnr" => $tplnr])) &&
                (!strcmp($this->check->getType($new_line->key), "Опора"))) {
                $this->bulk_line_bonds->update(["opory_tplnr" => $new_line->key, "line_tplnr" => $tplnr], ["opory_tplnr" => $new_line->key, "line_tplnr" => $tplnr], ["upsert" => true]);
            }
        }
    }

    private function checkKey(Map $points, Line &$new_line, string $tplnr): bool
    {
        if (!isset($points[$new_line->key]["coords"])) {
            $new_line->error_line = false;
            $this->error_count++;
            $this->logger->log("<li>      Ошибка в линии: " . $tplnr . " отсутсвует опора $new_line->key </li>\n");
            return true;
        }
        return false;
    }

    private function checkDublicate(Map $points, Line &$new_line, \SimpleXMLElement $line, string $tplnr): bool
    {
        $key_1 = (string)$line->point[0]->attributes()["point_code"];
        $key_2 = (string)$line->point[1]->attributes()["point_code"];
        $new_line->key = $key_1;
        if ($this->checkKey($points, $new_line, $tplnr)) {
            return true;
        }
        $new_line->key = $key_2;
        if ($this->checkKey($points, $new_line, $tplnr)) {
            return true;
        }
        if (($points[$key_1]["coords"][0] === $points[$key_2]["coords"][0]) &&
            ($points[$key_1]["coords"][1] === $points[$key_2]["coords"][1])) {
            $new_line->error_line = false;
            $this->error_count++;
            $this->logger->info("<li>      Ошибка в линии: " . $tplnr . " дублирование координат опор $key_1 и $key_2</li>\n");
            return true;
        }
        return false;
    }

    private function lineError(\SimpleXMLElement $line)
    {
        $this->error_count++;
        if (count($line->point) > 0) {
            $i = count($line->point) - 1;
            $key = "Код опоры " . (string)$line->point[$i]->attributes()["point_code"];
        } else {
            $key = "<b>Код опоры отсутствует</b>";
        }
        $this->logger->info("<li>      Ошибка в линии: " . $line->attributes()["tplnr"] . " всего опор: " .
            count($line->point) . " $key</li>\n");
    }

}
