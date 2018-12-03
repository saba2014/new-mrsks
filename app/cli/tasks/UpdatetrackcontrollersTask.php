<?php

use \Phalcon\Cli\Task as Task;
use MongoDB\Driver\Manager as Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use \Phalcon\Logger\Adapter\File as FileAdapter;

/*
 * function for comparing 2 elements in array, one of param in 'usort()' func
 */
function cmp($a, $b)
{
    if ($a->Actualmrtime == $b->Actualmrtime)
        return 0;
    if ($a->Actualmrtime < $b->Actualmrtime)
        return -1;
    else return 1;
}

class UpdateTrackControllersTask extends Task
{
    private $client, $tracksColl, $contrsColl, $metersColl, $maxTime, $colorCounter, $log;

    public function mainAction($arg)
    {
        $this->log = new FileAdapter($this->config->log->main);
        $start = date('Y-m', strtotime('-1 month')) . "-01";
        $end = date('Y-m-d');
        if (array_key_exists(0, $arg) && isset($arg[0])) {
            $start = $arg[0];
        }
        if (array_key_exists(1, $arg) && isset($arg[1])) {
            $end = $arg[1];
        }
        try {
            $data = $this->soapCall($start, $end);
        } catch (Exception $ex) {
            $this->log->error("Ошибка загрузки данных :".$ex->getMessage().
                " при вызове скрипта на датах: ".$start." и ".$end);
            echo $ex->getMessage() . " \n";
            return;
        }
        if (isset($data->EEablTab->item) && isset($data->ETe115Tab->item))
            $this->insertToBase($data->EEablTab->item, $data->ETe115Tab->item);
        else {
            $this->log->error("Ошибка загрузки данных: одна из таблиц пуста
             при вызове скрипта на датах: ".$start." и ".$end);
            echo "some trouble with data from SAP-wsdl server";
        }
    }

    public function initCollections()
    {
        $this->colorCounter = [0, 0, 0];
        $this->maxTime = ((int)$this->config->MCmax->hour) * 3600;
        $this->maxDistance = ((double)$this->config->MCmax->distance);
        $manager = new Manager();
        $this->contrsColl = new Collection($manager, $this->config->database->dbname, 'MobileControllers');
        $this->tracksColl = new Collection($manager, $this->config->database->dbname, 'MobileControllersTracks');
        $this->metersColl = new Collection($manager, $this->config->database->dbname, 'ElectricMeters');
        $this->tracksColl->deleteMany([]);
        $this->contrsColl->deleteMany([]);
        $this->metersColl->deleteMany([]);
    }

    public function createNewTrack($meter, $owner, $emei)
    {
        $obj = [];
        $this->colorCounter[0] += 5;
        $obj['type'] = "Feature";
        $obj['properties']['emei'] = $emei;
        $obj['properties']['owner'] = $owner->Name1;
        $obj['properties']['bukrs'] = $owner->Bukrs;
        $obj['properties']['day'] = $meter->Actualmrdate;
        $obj['properties']['times'] = [];
        $obj['properties']['times'][] = $meter->Actualmrtime;
        $obj['properties']['Ableser'] = $meter->Ableser;
        $obj['properties']['color'] = $this->getNewColor();
        $obj['geometry']['type'] = "LineString";
        $obj['geometry']['coordinates'] = [];
        $obj['geometry']['coordinates'][] = [(double)$meter->Longitude, (double)$meter->Latitude];
        return $obj;
    }


    public function insertController($control, $track, $emei)
    {
        $obj = [];
        $obj['type'] = "Feature";
        $obj['properties']['emei'] = $emei;
        $obj['properties']['Ableser'] = $control->Ableser;
        $obj['properties']['name'] = $control->Name1;
        $obj['properties']['bukrs'] = $control->Bukrs;
        $obj['geometry']['type'] = "Point";
        $last = count($track['geometry']['coordinates']);
        $obj['geometry']['coordinates'] = $track['geometry']['coordinates'][$last - 1];
        $this->contrsColl->insertOne($obj);
    }

    public function insertMeter($meter, $owner)
    {
        $obj = [];
        $obj['type'] = "Feature";
        $obj['properties']['Ablbelnr'] = $meter->Ablbelnr;
        $obj['properties']['Ableser'] = $meter->Ableser;
        $obj['properties']['day'] = $meter->Actualmrtime;
        $obj['properties']['HtmlData'] = $meter->HtmlData;
        $obj['properties']['Anlage'] = $meter->Anlage;
        $obj['properties']['owner'] = $owner->Name1;
        $obj['properties']['color'] = $this->getNewColor();
        $obj['properties']['bukrs'] = $owner->Bukrs;
        $obj['properties']['emei'] = $meter->Emei;
        $obj['geometry']['type'] = "Point";
        $obj['geometry']['coordinates'] = [];
        $obj['geometry']['coordinates'][] = (double)$meter->Longitude;
        $obj['geometry']['coordinates'][] = (double)$meter->Latitude;
        $this->metersColl->insertOne($obj);
    }


    public function insertTrack($track)
    {
        if (count($track['geometry']['coordinates']) == 1) {
            $track['geometry']['type'] = "Point";
            $track['geometry']['coordinates'] = $track['geometry']['coordinates'][0];
        }
        $this->tracksColl->insertOne($track);
    }

    public function changeLocalTrack(&$localTracks, $meter, $control)
    {
        $Emei = (string)$meter->Emei;
        if (isset($localTracks[$Emei]))
            $localTracks[$Emei]['meters'][] = $meter;
        else {
            $localTracks[$Emei] = [];
            $localTracks[$Emei]['meters'] = [];
            $localTracks[$Emei]['meters'][] = $meter;
            $localTracks[$Emei]['owner'] = $control;
            $localTracks[$Emei]['emei'] = $meter->Emei;
        }
    }

    public function insertTracks($meters, $control, $emei)
    {
        $track = $this->createNewTrack($meters[0], $control, $emei);
        $this->colorCounter[0] += 5;
        $this->insertMeter($meters[0], $control);
        $stDist = 0;
        $stTime = $meters[0]->Actualmrtime;
        for ($i = 1; $i < count($meters); $i++) {
            $finTime = $meters[$i]->Actualmrtime;
            $addDist = $this->getDistance($meters[$i], $meters[$i - 1]);
            if (
                ($meters[$i]->Actualmrdate != $track['properties']['day']) ||
                ($finTime - $stTime > $this->maxTime) ||
                ($stDist + $addDist > $this->maxDistance)
            ) {
                $this->colorCounter[0] += 5;
                $this->insertTrack($track);
                $stTime = $meters[$i]->Actualmrtime;
                $stDist = 0;
                $track = $this->createNewTrack($meters[$i], $control, $emei);
            } else {
                $stDist += $addDist;
                $track['geometry']['coordinates'][] = [(double)$meters[$i]->Longitude, (double)$meters[$i]->Latitude];
                $track['properties']['times'][] = $meters[$i]->Actualmrtime;
            }
            $this->insertMeter($meters[$i], $control);
        }
        $this->insertController($control, $track, $emei);
        $this->insertTrack($track);
    }

    public function insertToBase($electricMeters, $controllers)
    {
        //$flag = true;
        $this->initCollections();
        foreach ($electricMeters as $meter) {
            $meter->Actualmrtime = strtotime($meter->Actualmrdate . " " . $meter->Actualmrtime);
            $meter->Actualmrdate = strtotime($meter->Actualmrdate);
        }
        usort($electricMeters, "cmp");
        echo "controllers: " . (count($controllers)) . "\n";
        echo "electricMeters: " . (count($electricMeters)) . "\n";
        $i = 0;
        $count = count($controllers);
        $one = $count / 10;
        echo "*";
        foreach ($controllers as $control) {
            if (($i % $one) === 0) {
                echo "*";
            }
            $i++;
            $tempTracks = [];
            foreach ($electricMeters as $key => $meter) {
                if ($control->Ableser == $meter->Ableser) {
                    $this->changeLocalTrack($tempTracks, $meter, $control);
                    unset($electricMeters[$key]);
                }
            }
            if (count($tempTracks) > 0) {
                if (isset($tempTracks[''])) {
                    $tempTracks['']['emei'] = "NA";
                    // echo print_r($tempTracks['']);
                }
                foreach ($tempTracks as $track) {
                    $this->insertTracks($track['meters'], $track['owner'], $track['emei']);
                }
                // $this->insertTracks($tempTracks, $control);
                // break;
            }
            unset($tempTracks);
        }
    }

    /*
     * function that gives us distance(kilometers) between two points (which coords were given in Radian)
     */

    public function getDistance($meterOne, $meterTwo)
    {
        $R = 6371;
        $lat1 = (float)$meterOne->Latitude;
        $lat2 = (float)$meterTwo->Latitude;
        $lon1 = (float)$meterOne->Longitude;
        $lon2 = (float)$meterTwo->Longitude;
        $f1 = deg2rad($lat1);
        $f2 = deg2rad($lat2);
        $Dfi = deg2rad($lat2 - $lat1);
        $Dliambda = deg2rad($lon2 - $lon1);
        $a = sin($Dfi / 2) * sin($Dfi / 2) + cos($f1) * cos($f2) * sin($Dliambda / 2) * sin($Dliambda / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $d = $R * $c;
        return $d;
    }


    public function getNewColor()
    {
        if ($this->colorCounter[0] >= 255) {
            $this->colorCounter[1]++;
            $this->colorCounter[0] = 0;
        }
        if ($this->colorCounter[1] >= 255) {
            $this->colorCounter[2]++;
            $this->colorCounter[1] = 0;
        }
        $res = sprintf("#%02x%02x%02x", $this->colorCounter[0], $this->colorCounter[1], $this->colorCounter[2]);
        return $res;
    }

    public function soapCall($start, $end)
    {
        /*have to be in ini file*/
        $timeout = "1000";
        $url = 'http://soamanager:123456@er2dia02.sapsrv.ru:8001/sap/bc/srt/wsdl/flv_10002A101AD1/bndg_url/sap/bc/srt/rfc/mrsks/zisu_eabl2geo/200/zisu_eabl2geo/zisu_eabl2geo?sap-client=200';
        $func = '_-mrsks_-zisuEabl2geo';
        $params = [];
        $params['IDateAb'] = $start;
        $params['IDateBis'] = $end;

        //$params['IDateAb'] = "2017-11-01";
        //$params['IDateBis'] = "2017-11-30";
        $login = 'soamanager';
        $pass = '123456';
        /**/
        set_time_limit($timeout);
        ini_set("default_socket_timeout", $timeout);
        $cred = sprintf('Authorization: Basic %s', base64_encode($login . ':' . $pass));
        $opts = array(
            'http' => array(
                'method' => 'GET',
                'header' => $cred
            )
        );
        $context = stream_context_create($opts);
        $this->client = new SoapClient($url, array(
            'trace' => 1, 'stream_context' => $context, 'connection_timeout' => $timeout,
            'login' => $login, 'password' => $pass
        ));
        $params = array($params);
        try {
            $params = $this->client->__soapCall($func, $params);
        } catch (Exception $ex) {
            throw $ex;
        }
        file_put_contents("EEablTab.json", json_encode($params->EEablTab->item, JSON_UNESCAPED_UNICODE));
        file_put_contents("ETe115Tab.json", json_encode($params->ETe115Tab->item, JSON_UNESCAPED_UNICODE));
        return $params;
    }
}