<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use DS\Map;
use DS\Vector;
use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class CheckTplnr {

    public $line, $ps, $tp, $rp;
    private $path, $masks;

    public function __construct(string $path) {
        $this->path = $path;
        $XmlFile = fopen($this->path, "r");
        $xml = simplexml_load_file($this->path);
        fclose($XmlFile);
        $this->masks = new Vector();
        $this->line = new Vector();
        $this->ps = new Vector();
        $this->rp = new Vector();
        $this->tp = new Vector();
        $this->convertXml($xml->mask, $xml->color);
    }

    public function __destruct() {
        unset($this->masks);
        $this->destroyObjs();
    }

    private function destroyObjs(): void {
        unset($this->line);
        unset($this->ps);
        unset($this->rp);
        unset($this->tp);
    }

    private function convertXml(\SimpleXMLElement $xmlmasks, \SimpleXMLElement $xmlcolors): void {
        $color = new Map();
        $voltage = new Map();
        foreach ($xmlcolors as $color_volt) {
            $color[(string) $color_volt->attributes()['voltage_tplnr']] = (string) $color_volt->attributes()['color'];
            $voltage[(string) $color_volt->attributes()['voltage_tplnr']] = (float) $color_volt->attributes()['voltage'];
        }

        foreach ($xmlmasks as $mask) {
            $st = (string) $mask->attributes()['tplnr'];
            $mask_st = '/^';
            $mask_st = $mask_st . str_replace('+', '\d', $st);
            $new_mask = new Mask();
            $new_mask->type = (string) $mask->attributes()['type'];
            $new_mask->template = $mask_st . '$/m';
            $new_mask->voltage = (string) $mask->attributes()['voltage'];
            $new_mask->color = (string) $mask->attributes()['color'];
            $new_mask->limit = (int)$mask->attributes()['limit'];
            if (strlen($new_mask->voltage) == 0) {
                $new_mask->voltage = $voltage[mb_strimwidth((string) $mask->attributes()['tplnr'], 2, 3)];
            }
            if (strlen($new_mask->color) == 0) {
                $new_mask->color = $color[mb_strimwidth((string) $mask->attributes()['tplnr'], 2, 3)];
            }
            $type_line = mb_strimwidth((string) $mask->attributes()['tplnr'], 0, 1);
            if ($type_line === "V") {
                $new_mask->type_line = "ВЛ";
            }
            if ($type_line === "K") {
                $new_mask->type_line = "КЛ";
            }
            $this->masks->push($new_mask);
        }
    }

    public function getType(string $tplnr): string {
        $mask = $this->getMask($tplnr);
        if (isset($mask) && strcmp($mask->type, "")) {
            return $mask->type;
        } else {
            return "";
        }
    }

    public function getTypeLine(string $tplnr): string {
        $mask = $this->getMask($tplnr);
        if (isset($mask) && strcmp($mask->type_line, "")) {
            return $mask->type_line;
        } else {
            return "";
        }
    }

    public function getMask(string $tplnr) {
        for ($i = 0; $i < $this->masks->count(); $i++) {
            if (preg_match($this->masks[$i]->template, $tplnr)) {
                if (strcmp($this->masks[$i]->type, "Опора")) {
                    $st = $this->getTypeVl($tplnr);
                    if (strcmp($st, "")) {
                        return $st;
                    } else {
                        return $this->masks[$i];
                    }
                }
                return $this->masks[$i];
            }
        }
        return "";
    }

    private function getTypeVl(string $type): string {
        $st = mb_strimwidth($type, 0, strlen($type) - 5);
        for ($i = 0; $i < $this->masks->count(); $i++) {
            if (preg_match($this->masks[$i]->template, $st)) {
                return $this->masks[$i];
            }
        }
        return "";
    }

    public function saveSortMask(string $db, string $lines, string $ps, string $path):
    void {
        $data = new Map();
        $data['data_1'] = getdate();
        $manager = new Manager();
        $this->db_ps = new Collection($manager, $db, $ps);
        $this->db_line = new Collection($manager, $db, $lines);
        $this->sortMask();
        $data['line'] = $this->line;
        $data['rp'] = $this->rp;
        $data['tp'] = $this->tp;
        $data['ps'] = $this->ps;
        file_put_contents($path, json_encode($data), LOCK_EX);
    }

    public function loadSortMask(string &$path): void {
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            $this->destroyObjs();
            $this->line = new Vector($data['line']);
            $this->rp = new Vector($data['rp']);
            $this->tp = new Vector($data['tp']);
            $this->ps = new Vector($data['ps']);
        }
    }

    private function sortMask(): void {
        for ($i = 0; $i < $this->masks->count(); $i++) {
            $mask = $this->masks[$i];
            $query = ["properties.kVoltage" => $mask->color, "properties.Voltage" => $mask->voltage,
                "properties.TypeByTplnr" => $mask->type];
            $query_line = ["properties.kVoltage" => $mask->color, "properties.Voltage" => $mask->voltage,
                "properties.TypeByTplnr" => $mask->type, "properties.type" => $mask->type_line];
            $this->switchElements($query, $query_line, $mask);
        }
    }

    private function switchElements(array $query, array $query_line, &$mask): void {
        switch ($mask->type) {
            case "ЛЭП" :
                if ($this->db_line->findOne($query_line)) {
                    $this->addElm($mask, $this->line);
                }
                break;

            case "РП" :
                if ($this->db_ps->findOne($query)) {
                    $this->addElm($mask, $this->rp);
                }
                break;

            case "ТП" :
                if ($this->db_ps->findOne($query)) {
                    $this->addElm($mask, $this->tp);
                }
                break;

            case "Подстанции" :
                if ($this->db_ps->findOne($query)) {
                    $this->addElm($mask, $this->ps);
                }
                break;
        }
    }

    private function addElm(&$elm, Vector &$arr): void {
        $i = 0;
        for ($i = 0; $i < $arr->count(); $i++) {
            if ($arr[$i]->voltage < $elm->voltage) {
                $arr->insert($i, $elm);
                return;
            }
            if (($arr[$i]->voltage === $elm->voltage) && (!strcmp($arr[$i]->color, $elm->color))) {
                return;
            }
        }
        $arr->push($elm);
    }

}
