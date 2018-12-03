<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

use \Ds\Map;
use \Ds\Vector;
use \MongoDB\Driver\BulkWrite;
use \MongoDB\Driver\Manager;
use \Phalcon\Logger\Adapter\File as FileAdapter;
use \SimpleXMLElement;
use NavikeyCore\Library\PSArea as PsArea;
use \Phalcon\Db\Adapter\MongoDB\Collection;

/**
 * Description of Core
 *
 * @author alex
 */
class PS extends ElectricObjects
{

    private $error_count, $check, $logger;

    public function __construct(string $path, FileAdapter &$logger, string &$db)
    {
        $this->error_count = 0;
        $this->check = new CheckTplnr($path);
        $this->logger = $logger;
        $this->db = $db;
    }

    public function findPs(string $tplnr)
    {
        $manager = new Manager();
        $coll = new Collection($manager, $this->db, 'Ps');
        $ps = $coll->find(["properties.tplnr" => $tplnr]);
        $mongoData = $ps->toArray();
        if (count($mongoData) == 0) return false;
        else {
            return $mongoData;
        }
    }


    protected function createPoint($ps)
    {
        $res = [];
        $res['type'] = "Point";
        $res['coordinates'] = [
            (float)$ps->point->attributes()["coord_long"],
            (float)$ps->point->attributes()["coord_lat"]
        ];
        return $res;
    }

    protected function createPolygon($ps)
    {
        $res = [];
        $res['type'] = "Polygon";
        $res['coordinates'] = [];
        $res['coordinates'][] = [];
        $arr = $ps->point;
        for ($i = 0; $i < count($ps->point); $i++) {
            $res['coordinates'][0][] = [
                (float)$ps->point[$i]->attributes()['coord_long'],
                (float)$ps->point[$i]->attributes()['coord_lat']
            ];
        }
        $res['coordinates'][0][] = [
            (float)$ps->point[0]->attributes()['coord_long'],
            (float)$ps->point[0]->attributes()['coord_lat']
        ];
        return $res;
    }

    public function findGeometry($oldGeometry, $geometryName)
    {
        if (!strcmp($oldGeometry->type, "GeometryCollection")) {
            for ($i = 0; $i < count($oldGeometry->geometries); $i++) {
                $name = $oldGeometry->geometries[$i]->type;
                if (!strcmp($name, $geometryName)) {
                    return $oldGeometry->geometries[$i];
                }
            }
        } else {
            if (!strcmp($oldGeometry->type, $geometryName))
                return $oldGeometry;
            else return false;
        }
    }

    public function oldGeometryMigration(Map &$data, SimpleXMLElement &$ps, $oldData = false)
    {
        $newGeometry = [];
        if (count($ps->point) > 1) {
            $polygon = $this->createPolygon($ps);
            if ($oldData != false)
                $point = $this->findGeometry($oldData[0]->geometry, "Point");
        } else {
            $point = $this->createPoint($ps);
            if ($oldData != false)
                $polygon = $this->findGeometry($oldData[0]->geometry, "Polygon");
        }
        $newGeometry['type'] = "GeometryCollection";
        $newGeometry['geometries'] = [];
        if ($point) {
            $newGeometry['geometries'][] = $point;
        }
        if ($polygon) {
            $newGeometry['geometries'][] = $polygon;
        }
        $data['geometry'] = $newGeometry;
    }


    public function load(SimpleXMLElement $xmlpoints, Map &$points): void
    {
        $manager = new Manager();
        $bulk_ps = new BulkWrite();
        $this->logger->info("<b>Всего подстанций: " . count($xmlpoints) . "<br /></b><ul>\n");
        $xmlps = $xmlpoints;
        $this->error_count = 0;
        foreach ($xmlps as $ps) {
            if (!isset($ps->attributes()["tplnr"])) {
                $this->logger->info("<li> У подстанции " . $ps->attributes()["d_name"] . "(" .
                    ") отсутсвует идентификатор TPLNR!</li>\n");
                continue;
            }
            $this->dataAdd($ps, $bulk_ps);
        }
        try {
            if ($bulk_ps->count()) {
                $manager->executeBulkWrite("$this->db.Ps", $bulk_ps);
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
//            echo "<li>     Ошибка записи в базу: " . $e->getMessage() . "<br> Код ошибки: " . 
//                    $e->getCode() . "</li>";
            $this->logger->error("<li>     Ошибка записи в базу: " . $e->getMessage() . "<br> Код ошибки: " .
                $e->getCode() . "</li>");
        }
        $this->logger->info("</ul> " . $this->error_count . " ошибок в подстанциях.<br/>\n");
    }

    private function dataAdd(SimpleXMLElement &$ps, BulkWrite &$mongo): void
    {
        $data = new Map();
        $data["type"] = "Feature";
        $this->dataSetType($data, $ps);
        $this->dataAddMainInfo($data, $ps);
        $this->dataAddTplnr($data, $ps);
        $this->dataAddStatus($data, $ps);
        $type = $data["properties"]["TypeByTplnr"];
        if ((!strcmp($type, "ТП")) || (!strcmp($type, "Подстанции"))) {
            $this->dataAddAdditional($data, $ps);
        }
        $oldData = $this->findPs((string)$ps->attributes()["tplnr"]);
        $this->oldGeometryMigration($data, $ps, $oldData);
        $error_ps = $this->dataCheck($data, $ps);
        $arrData = $data->toArray();
        $this->dataLoad($data, $mongo, $error_ps);
    }

    private function dataSetType(Map &$data, SimpleXMLElement &$ps): void
    {
        $match = "";
        $found = preg_match('/([A-Z]+)(\d+)-(\d+)/', (string)$ps->attributes()["tplnr"], $match);
        $data["properties"] = [];
        if ($found > 0) {
            switch ($match[1]) {
                case "TP":
                    $data["properties"]["oTypePS"] = "2";
                    break;
                case "PS":
                    $data["properties"]["oTypePS"] = "3";
                    break;
                case "RP":
                default :
                    $data["properties"]["oTypePS"] = "1";
                    break;
            }
        } else {
            $data["properties"]["oTypePS"] = "1";
        }
    }

    private function dataAddAddres(Map &$data, SimpleXMLElement &$ps): void
    {
        $data["properties"]["addr_region"] = (string)$ps->attributes()['addr_region'];
        $data["properties"]["addr_district"] = (string)$ps->attributes()['addr_district'];
        $data["properties"]["addr_city"] = (string)$ps->attributes()['addr_city'];
        $data["properties"]["addr_street"] = (string)$ps->attributes()['addr_street'];
        $data["properties"]["addr_house"] = (string)$ps->attributes()['addr_house'];
        $data["properties"]["addr_building"] = (string)$ps->attributes()['addr_building'];
        $data["properties"]["addr_pcode"] = (string)$ps->attributes()['addr_pcode'];
        $data["properties"]["addr_country"] = (string)$ps->attributes()['addr_country'];
    }

    private function dataAddMainInfo(Map &$data, SimpleXMLElement &$ps): void
    {
        if (isset($ps->attributes()["address"]))
            $data["properties"]["address"] = (string)$ps->attributes()["address"];
        if (isset($ps->attributes()["location"]))
            $data["properties"]["location"] = (string)$ps->attributes()["location"];
        $this->dataAddAddres($data, $ps);
        $temp = $data->toArray();
        //$data["properties"]["op_resp"] = (string) $ps->attributes()["op_resp"];
        if (isset($ps->balance)) {
            $data["properties"]["balance"] = (string)$ps->balance->attributes()["code"];
            $data["properties"]["balance_name"] = (string)$ps->balance->attributes()["name"];
        }
        $data["properties"]["kl_u"] = (string)$ps->attributes()["kl_u"];
        //$data["properties"]["d_name"] = mb_convert_encoding((string)$ps->attributes()["d_name"], 'Windows-1251', 'UTF-8');
        $data["properties"]["d_name"] = (string)$ps->attributes()["d_name"];
        $data["properties"]["TypeByTplnr"] = '';
    }

    private function dataAddTplnr(Map &$data, SimpleXMLElement &$ps): void
    {
        $tplnr = $this->dataGetTplnr($data, $ps);
        $data["properties"]["tplnr"] = $tplnr;
        $mask = $this->check->getMask($tplnr);
        $data["properties"]["kVoltage"] = $mask->color;
        $data["properties"]["Voltage"] = $mask->voltage;
    }

    private function dataAddStatus(Map &$data, SimpleXMLElement &$ps): void
    {
        if (isset($ps->sysstat)) {
            $sysstat = [];
            foreach ($ps->sysstat as $stat) {
                array_push($sysstat, (string)$stat->attributes()["name"]);
                //$sysstat->push((string) $stat->attributes()["name"]);
            }
            $data["properties"]["sysstat"] = $sysstat;
        }
        if (isset($ps->usrstat)) {
            $usrstat = [];
            foreach ($ps->usrstat as $stat) {
                array_push($usrstat, (string)$stat->attributes()["name"]);
                //$usrstat->push((string) $stat->attributes()["name"]);
            }
            $data["properties"]["usrstat"] = $usrstat;
        }
    }

    private function dataGetTplnr(Map &$data, SimpleXMLElement &$ps): string
    {
        $tplnr = mb_strtoupper((string)$ps->attributes()["tplnr"], 'UTF-8');
        $type = $this->check->getType($tplnr);
        if ($type) {
            $data["properties"]["TypeByTplnr"] = $type;
            if (strcmp($type, "Подстанции") && strcmp($type, "РП") && strcmp($type, "ТП")) {
                $this->logger->info("<li>Указан не тот тип для ПС/ТП/РП TPLNR = " . $tplnr . " тип = " . $type .
                    "</li>");
                $this->error_count++;
            }
        } else {
            $this->logger->info("<li>TPLNR = " . $tplnr . " не найдено </li> ");
            $this->error_count++;
        }
        return $tplnr;
    }

    private function dataAddAdditional(Map &$data, SimpleXMLElement &$ps): void
    {
        $data["properties"]["additional"] = [];
        if (count($ps->transformer) > 0) {
            $data["properties"]["additional"]["transformer"] = new Vector();
            foreach ($ps->transformer as $item) {
                $data["properties"]["additional"]["transformer"]->push($item->attributes()["power"]);
            }
        }
        $this->dataAddRoot($data, $ps);
        $reserve_boxs = [];
        foreach ($ps->reserve_box as $box) {
            $reserve_boxs[str_replace(".", ",", (string)$box->attributes()["class"])] = (integer)$box->attributes()["number"];
        }
        ksort($reserve_boxs);
        $data["properties"]["additional"]["res_box"] = $reserve_boxs;
    }

    private function dataAddRoot(Map &$data, SimpleXMLElement &$ps): void
    {
        if (count($ps->res_pow_cons_cotr_appl) > 0) {
            $data["properties"]["additional"]["res_pow_cons_cotr_appl"] = (double)$ps->res_pow_cons_cotr_appl->attributes()["value"];
        }

        if (count($ps->nagr_nminus1) > 0)
            $data["properties"]["additional"]["nagr_nminus1"] = (double)$ps->nagr_nminus1->attributes()["value"];
        if (count($ps->pow_cotr) > 0) {
            $data["properties"]["additional"]["pow_cotr"] = (double)$ps->pow_cotr->attributes()["value"];
        }

        if (count($ps->pow_appl) > 0) {
            $data["properties"]["additional"]["pow_appl"] = (double)$ps->pow_appl->attributes()["value"];
        }

        if (count($ps->root) > 0) {
            $data["properties"]["additional"]["root_name"] = (string)$ps->root->attributes()["name"];
        }

        if (count($ps->root) > 0) {
            $data["properties"]["additional"]["root_tplnr"] = (string)$ps->root->attributes()["tplnr"];
        }

        if (count($ps->root_switchgear) > 0) {
            $data["properties"]["additional"]["root_switchgear"] = (string)$ps->root_switchgear->attributes()["name"];
        }
    }

    private function dataCheck(Map &$data, SimpleXMLElement &$ps): bool
    {
        $error_ps = $this->dataCheckCoords($data, $ps);

        if (((float)$ps->point->attributes()["coord_long"] != 0) &&
            ((float)$ps->point->attributes()["coord_lat"] != 0)) {
            /*$data["geometry"]["coordinates"] = [(float)$ps->point->attributes()["coord_long"],
                (float)$ps->point->attributes()["coord_lat"]];*/
        } else {
            $error_ps = false;
            $this->error_count++;
            $this->logger->info("<li>      Ошибка в подстанции: (TPLNR: " . $data["properties"]["tplnr"] . ") " .
                " (" . $data["properties"]["d_name"] . ") не верные координаты [" .
                $ps->point->attributes()["coord_lat"] . ", " .
                $ps->point->attributes()["coord_long"] . "] </li>\n");
        }
        return $error_ps;
    }

    private function dataCheckCoords(Map &$data, SimpleXMLElement &$ps): bool
    {
        $error_ps = true;
        if (((((float)$ps->point->attributes()["coord_long"]) > 180)) ||
            (((float)$ps->point->attributes()["coord_lat"]) > 90)) {
            $error_ps = false;
            $this->error_count++;
            $this->logger->info("<li>      Ошибка в подстанции: (TPLNR: " . $data["properties"]["tplnr"] . ") " .
                " (" . $data["properties"]["d_name"] .
                ") не верные координаты (большие значения) [" . $ps->point->attributes()["coord_lat"] . ", " .
                $ps->point->attributes()["coord_long"] . "]</li>\n");
        }
        return $error_ps;
    }

    private function dataLoad(Map &$data, BulkWrite &$mongo, bool $error_ps)
    {
        if (($error_ps) && (isset($data))) {
            $arr = $data->toArray();
            if (isset($arr['properties']['additional']['transformer']))
                $arr['properties']['additional']['transformer'] = $arr['properties']['additional']['transformer']->toArray();
            $mongo->update(
                ["properties.tplnr" => $data["properties"]["tplnr"]], $arr, ["upsert" => true]);
        }
    }

}
