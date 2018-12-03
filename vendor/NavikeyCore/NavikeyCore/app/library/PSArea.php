<?php

namespace NavikeyCore\Library;

use \Ds\Map;
use \Ds\Vector;
use \MongoDB\Driver\BulkWrite;
use \MongoDB\Driver\Manager as Manager;
use \Phalcon\Logger\Adapter\File as FileAdapter;
use \SimpleXMLElement;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use NavikeyCore\Library\Checker as Checker;

/*
 * PSArea class that is suppose to parse and load ps' area polygon
 */

class PSArea extends ElectricObjects
{
    private $db, $coll, $check, $logger, $checker;

    public function __construct($check, FileAdapter &$logger, string &$db){
        $this->db = $db;
        $manager = new Manager();
        $this->check = $check;
        $this->logger = $logger;
        $this->checker = new Checker($logger);
        $this->coll = new Collection($manager, $this->db, "PsArea");
    }

    public function load(SimpleXMLElement $xml, Map &$points): void {
        $manager = new Manager();
        $bulk_ps = new BulkWrite();
        $xmlps = $xml->point;
        $this->error_count = 0;
        foreach ($xmlps as $ps) {
            if ((!isset($ps->attributes()["coord_lat"]))||( !isset($ps->attributes()["coord_long"])) ) {
                $this->logger->info("<li> В площади ошибка в координатах</li>\n");
                continue;
            }
        }
        if (!$this->checker->checkIfRightHanded($xmlps)){
            $this->error_count += $this->checker->errorCounter;
            return;
        }
        $this->addData($xmlps,$xml, $bulk_ps);
        //$this->addData($xmlps,$xml, $bulk_ps);
        try {
            if ($bulk_ps->count()) {
                $manager->executeBulkWrite("$this->db.PsArea", $bulk_ps);
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            $this->logger->error("<li>     Ошибка записи в базу: " . $e->getMessage() . "<br> Код ошибки: " .
                $e->getCode() . "</li>");
        }
    }

    public function insertLinesInfo(SimpleXMLElement $xmllines,&$data){
        $data['outLines']=[];
        $data['inLines']=[];
        foreach ($xmllines as $line){
            $obj = [];
            $obj['tplnr'] = (string)$line->attributes()['tplnr'];
            $obj['d_num'] = (string)$line->attributes()['d_num'];
            $obj['id'] = (string)$line->attributes()['id'];
            if (strcmp($line->attributes()['in_out'],"OUT")==0){
                $data['outLines'][]=$obj;
            }
            if (strcmp($line->attributes()['in_out'],"IN")==0){
                $data['inLines'][]=$obj;
            }
        }
    }

    public function addData(SimpleXMLElement $xmlpoints,SimpleXMLElement $ps ,BulkWrite $mongo){
        $data = new Map();
        $data['type']="Feature";
        $data['properties']=[];
        $data['geometry']=[];
        $data['geometry']['type']="Polygon";
        $data['geometry']['coordinates']=[];
        $data['geometry']['coordinates'][0]=[];
        foreach ($xmlpoints as $point){
            $data['geometry']['coordinates'][0][]=[
                (double)$point->attributes()['coord_long'],
                (double)$point->attributes()['coord_lat']
            ];
        }
        $data['geometry']['coordinates'][0][]=[
            (double)$xmlpoints[0]->attributes()['coord_long'],
            (double)$xmlpoints[0]->attributes()['coord_lat']
        ];
        $data['properties']['name']=(string)$ps->attributes()['d_name'];
        $data['properties']['tplnr']=(string)$ps->attributes()['tplnr'];
        $data['properties']['kl_u'] =(string)$ps->attributes()['kl_u'];
        $mask = $this->check->getMask($data['properties']['tplnr']);
        $data["properties"]["kVoltage"] = $mask->color;
        $data["properties"]["Voltage"] = $mask->voltage;
        $data['properties']['TypeByTplnr'] = $this->check->getType(
            $data['properties']['tplnr']
        );
        $this->setType($data,$ps);
        $res = $data->toArray();
        $this->insertLinesInfo($ps->line,$res['properties']);
        $this->loadData($res,$mongo);
    }

    public function setType(Map &$data, SimpleXMLElement &$ps){
        $match = "";
        $found = preg_match('/([A-Z]+)(\d+)-(\d+)/', (string) $ps->attributes()["tplnr"], $match);
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

    public function loadData($data,$mongo){
        $mongo->update(
            ["properties.tplnr" => $data["properties"]["tplnr"]],$data, ["upsert" => true]
        );
    }

}