<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use \Ds\Map;
use \Phalcon\Logger\Adapter\File as FileAdapter;

/**
 * Description of Core
 *
 * @author alex
 */
class Oporys extends ElectricObjects {

    private $error_count, $check, $logger;

    public function __construct(string $path, FileAdapter &$logger) {
        $this->error_count = 0;
        $this->check = new CheckTplnr($path);
        $this->logger = $logger;
    }

    public function load(\SimpleXMLElement $xmlpoints, Map &$points) {
        $this->logger->info("<b>Всего опор: " . count($xmlpoints) . "<br /></b><ul>\n");
        $this->error_count = 0;
        foreach ($xmlpoints as $point) { // Создаем массив  и записываем в базу опоры
            if ((!isset($point->attributes()["point_code"])) ||
                    (!strcmp((string) $point->attributes()["point_code"], ''))) {
                $this->logger->info("<li> У опоры " . $point->attributes()["name"] .
                        " отсутсвует идентификатор POINT_CODE!</li>\n");
                $this->error_count++;
                continue;
            }
            $this->loadPoint($point, $points);
        }
        $this->logger->info("</ul>" . $this->error_count . " ошибок в опорах.<hr/>\n");
    }

    private function loadPoint(\SimpleXMLElement &$point, Map &$points): void {
        $key = (string) $point->attributes()["point_code"];
        $points[$key] = new Map();
        $points[$key]["load"] = false;
        if (((float) $point->attributes()["coord_long"] != 0) &&
                ((float) $point->attributes()["coord_lat"] != 0)) {

            $this->pointCheck($point, $points, $key);
            $data = new Map;
            $this->dataAddMainInfo($points, $point, $key, $data);
            $error_opory = $this->dataAddTplnr($data, $point);
            $this->setColor($data, $key);

            // здесь в data находится опора которую можно записать в базу опор
            if ($error_opory) {
                $points[$key]["data"] = $data;
            }
        } else {
            $this->logger->info("<li>      Ошибка в опоре: $key - координаты [" . $point->attributes()["coord_lat"] .
                    ", " . $point->attributes()["coord_long"] . "] </li>\n");
            $this->error_count++;
        }
    }

    private function pointCheck(\SimpleXMLElement &$point, Map &$points, string $key): bool {
        if ((((float) $point->attributes()["coord_long"] > 180)) ||
                ((float) $point->attributes()["coord_lat"] > 90)) {
            $this->error_count++;
            $error_opory = false;
            $this->logger->info("<li>      Ошибка в опоре: $key неверные координаты (большие значения) [" .
                    $point->attributes()["coord_lat"] . ", " . $point->attributes()["coord_long"] .
                    "]</li>\n");
        } else {
            $error_opory = true;
            $points[$key]["coords"] = [(float) $point->attributes()["coord_long"],
                (float) $point->attributes()["coord_lat"]];
        }
        return $error_opory;
    }

    private function dataAddMainInfo(Map &$points, \SimpleXMLElement $point, string $key, Map &$data)
    : void {
        $points[$key]["name"] = (string) $point->attributes()["name"];
        $points[$key]["alt"] = (string) $point->attributes()["coord_alt"];
        $points[$key]["balance_name"] = (string) $point->attributes()["balance_name"];
        $data["type"] = "Feature";
        $data["properties"] = [];
        $data["properties"]["NoInLine"] = $points[$key]["name"];
        $data["properties"]["alt"] = $points[$key]["alt"];
        $data["properties"]["tplnr"] = $key;
        $data["properties"]["TypeByTplnr"] = '';
        $data["properties"]["balance_name"] = $points[$key]["balance_name"];
        $data["geometry"] = [];
        $data["geometry"]["type"] = "Point";
        if (isset($points[$key]["coords"])) {
            $data["geometry"]["coordinates"] = $points[$key]["coords"];
        }
        if (isset($point->attributes()["location"]))
        {
            $data["properties"]["location"] = (string) $point->attributes()["location"];
        }
        //unset($some);
    }

    private function dataAddTplnr(Map &$data, \SimpleXMLElement &$point): bool {
        $tplnr = mb_strtoupper((string) $point->attributes()["point_code"], 'UTF-8');
        $type = $this->check->getType($tplnr);
        $error_opory = true;
        if ($type) {
            $data["properties"]["TypeByTplnr"] = $type;
            if (strcmp($type, "Опора")) {
                $error_opory = false;
            }
        } else {
            $this->logger->info("<li>TPLNR = " . $tplnr . " не найдено </li> ");
            $this->error_count++;
        }
        return $error_opory;
    }

    private function setColor(Map &$data, string $tplnr): void {
        $mask = $this->check->getMask($tplnr);
        $data["properties"]["tplnr"] = $tplnr;
        $data["properties"]["kVoltage"] = $mask->color;
        $data["properties"]["Voltage"] = $mask->voltage;
    }

}
