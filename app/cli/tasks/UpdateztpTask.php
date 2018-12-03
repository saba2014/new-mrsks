<?php

use \Phalcon\Cli\Task as Task;
use \MongoDB\Driver\BulkWrite as BulkWrite;
use \MongoDB\Driver\Manager as Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;


/*
 * script that is suppose to update/delete/insert data in Ztp collection
 */
class UpdateZtpTask extends Task{

    private $db, $answer, $client;

    public function mainAction(){
        $this->db = $this->config->database->dbname;
        $res = $this->soapCall();
        if (isset($res)) {
            if (isset($res->item)) {
                $this->answer = [['I_TP'=>['item'=>[]]]];
                $this->insertToDB($res->item);
            }
            else echo "no objects to update"."\n";
        }
        else echo "no objects to update"."\n";
    }

    public function insertToDB($objects){
        $manager = new Manager();
        $ztp = new BulkWrite();
        //echo count($objects).'\n';
        foreach ($objects as $tObject) {
            $tData = [];
            $tData['properties']['doknr']=$tObject->DOKNR;
            $tData['properties']['date']=$tObject->DATE;
            $tData['properties']['power']=(float)$tObject->POWER;
            $tData['properties']['status']=$tObject->STATUS;
            $tData['properties']['date_del']=$tObject->DATE_DEL;
            if ($tObject->TU_REQ_KAPZATR)
                if ($tObject->TU_REQ_KAPZATR!="")
                {
                $tData['properties']['kapzatr']=$tObject->TU_REQ_KAPZATR;
                }
            $tData['properties']['data_okon'] = $tObject->DATA_OKON;
            $tData['properties']['vaoltage'] = $tObject->VOLTAGE_VAL;
            $tData['properties']['category'] = $tObject->CATEGORY;
            $tData['properties']['main_tplnr_point'] = $tObject->MAIN_TPLNR_POINT;
            $tData['properties']['main_tplnr_ps'] = $tObject->MAIN_TPLNR_PS;
            $tData['properties']['rezerv_tplnr_point'] = $tObject->REZERV_TPLNR_POINT;
            $tData['properties']['rezerv_tplnr_ps'] = $tObject->REZERV_TPLNR_PS;
            $tData['properties']['ztu_dokrn']=$tObject->ZTU_DOKNR;
            $tData['type']='Feature';
            $tData['geometry']['type']='Point';
            $tData['geometry']['coordinates']=[];
            $tData['geometry']['coordinates'][]=(float)$tObject->LONGITUDE;
            $tData['geometry']['coordinates'][]=(float)$tObject->LATITUDE;
            if(!strcmp($tObject->DATE_DEL,"0000-00-00")) {
                $ztp->update(
                    [ "properties.doknr"=>$tData['properties']['doknr'] ],
                    $tData,
                    ["upsert"=>true]
                );
            } else {
                $ztp->delete(
                    ['properties.doknr'=>$tData['properties']['doknr']]
                );
            }
            $this->answer[0]['I_TP']['item'][]=$tObject;
        }
        if ($ztp->count()) {
            $manager->executeBulkWrite("$this->db.Ztp", $ztp);
        }

            $this->confirmSoapCall('_-MRSKS_-TP_2_GIS_CONFIRM');
    }

    public function confirmSoapCall($func){
        /*have to be in ini file*/
        $timeout = "300000";
        $url = 'http://er2dia04.sapsrv.ru:8001/sap/bc/srt/wsdl/flv_10002A101AD1/bndg_url/sap/bc/srt/rfc/mrsks/tp_2_gis/200/mrsks_tp_2_gis/mrsks_tp_2_gis?sap-client=200';
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
        try {
            $respose = $this->client->__soapCall($func, $this->answer);
        } catch (Exception $ex) {
            echo $ex->getMessage() . " \n";
        }
    }

    public function soapCall()
    {
        /*have to be in ini file*/
        $timeout = "300000";
        $url = 'http://er2dia04.sapsrv.ru:8001/sap/bc/srt/wsdl/flv_10002A101AD1/bndg_url/sap/bc/srt/rfc/mrsks/tp_2_gis/200/mrsks_tp_2_gis/mrsks_tp_2_gis?sap-client=200';
        $func = '_-MRSKS_-TP_2_GIS';
        $params = [];
        $params['E_TP']=[];
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
            echo $ex->getMessage() . " \n";
        }
        if(isset($params->E_TP->item))
            file_put_contents("E_TP.json", json_encode($params->E_TP->item, JSON_UNESCAPED_UNICODE));
        return $params->E_TP;
    }

}