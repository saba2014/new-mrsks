<?php

declare(strict_types = 1);

use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use Phalcon\Cli\Task;

/*
 * Task that updates Loss - collection via wsdl server
 */
class LossupdateTask extends Task
{

    public function mainAction()
    {
        $manager = new Manager();
        $losses = new Collection($manager, $this->config->database->dbname, "Loss");
        $cred = sprintf('Authorization: Basic %s', base64_encode('soamanager:123456'));
        $opts = array(
            'http' => array(
                'method' => 'GET',
                'header' => $cred, ),
        );

        $tUrl = 'http://er2dia02.sapsrv.ru:8001/sap/bc/srt/wsdl/flv_10002A101AD1/srvc_url/sap/bc/srt/rfc/mrsks/isu_balance2geo/200/zisu_balance2geo/zisu_balance2geo?sap-client=200';
        $context = stream_context_create($opts);
        $client = new SoapClient($tUrl, array('trace' => 1, 'stream_context' => $context));
        $soap_array = array(
            array('E_TABLE' => array()),
        );
        $count = 0;
        $tResponse = $client->__soapCall('_-MRSKS_-ISU_BALANCE2GEO', $soap_array);
        if (isset($tResponse->E_TABLE->item)) {
            foreach ($tResponse->E_TABLE->item as $tObject) {
                $tData = [];
                $tData['properties']['unique_key'] = (int) $tObject->UNIQUE_KEY;
                $tData['properties']['tplnr'] = $tObject->TPLNR;
                $tData['properties']['date'] = $tObject->ADAT;
                $tData['properties']['date_ab'] = $tObject->ADAT_AB;
                $tData['properties']['fider_input'] = (float) $tObject->FIDER_INPUT;
                $tData['properties']['po_all'] = (float) $tObject->PO_ALL;
                $tData['properties']['po_jur'] = (float) $tObject->PO_JUR;
                $tData['properties']['po_phys'] = (float) $tObject->PO_PHYS;
                $tData['properties']['loss_all'] = (float) $tObject->LOSS_ALL;
                $tData['properties']['loss_all_pr'] = (float) $tObject->LOSS_ALL_PR;
                $tData['properties']['count_askue_jur'] = (int) $tObject->COUNT_ASKUE_JUR;
                $tData['properties']['count_askue_fis'] = (int) $tObject->COUNT_ASKUE_FIS;
                $tData['properties']['count_askue_all'] = (int) $tObject->COUNT_ASKUE_ALL;
                $tData['properties']['count_non_askue'] = (int) $tObject->COUNT_NON_ASKUE;
                $tData['properties']['count_jur'] = (int) $tObject->COUNT_JUR;
                $tData['properties']['count_fis'] = (int) $tObject->COUNT_FIS;
                $tData['properties']['count_all'] = (int) $tObject->COUNT_ALL;
                $tData['properties']['color'] = $tObject->COLOR_2_GEO;
                try {
                    $losses->updateOne(
                        [
                            "properties.unique_key" => $tData['properties']['unique_key'],
                            "properties.tplnr" => $tData['properties']['tplnr']
                        ],
                        [
                            '$set' => $tData
                        ],
                        [
                            'upsert' => true
                        ]
                    );
                }
                catch (MongoCursorException $err)
                {
                    $count++;
                    echo "error:".$err->getMessage()."\n";
                }
            }
        }
        unset($manager);
        unset($losses);
        unset($client);
    }
}