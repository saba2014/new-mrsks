<?php


use \Phalcon\Cli\Task as Task;
use MongoDB\Driver\Manager as Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use \Phalcon\Logger\Adapter\File as FileAdapter;
use MongoDB\BSON\Regex as Regex;


class SenddataTask extends Task
{
    private $name, $pass, $url, $db, $acc, $ref, $exp;

    public function mainAction(array $arg)
    {
        if(!isset($arg[0]) || !isset($arg[1])) {
            echo "Need DB name and url\n";
            return;
        }
        $this->name = "alex";
        $this->pass = "str";
        $this->url = $arg[1];
        $this->db = $arg[0];
        try {
            $this->logIn();
            $this->getDataFromDb();
        } catch (Exception $ex) {
            echo $ex->getMessage() . "\n";
        }
    }

    private function getDataFromDb()
    {
        $locFilter = ["properties.location" => ['$regex' => "2"]];
        $lon1 = 89.13409423828126;
        $lon2 = 94.25607299804689;
        $lat1 = 54.67138928829547;
        $lat2 = 57.27462392245362;
        $geoFilter = ['geometry' => ['$geoIntersects' => ['$geometry' => ['type' => 'Polygon',
            'coordinates' => [
                [
                    [$lon1, $lat1],
                    [$lon1, $lat2],
                    [$lon2, $lat2],
                    [$lon2, $lat1],
                    [$lon1, $lat1],
                ],
            ],
        ]]]];
        $complexFilter = [
            'geometry' => ['$geoIntersects' => ['$geometry' => ['type' => 'Polygon',
                'coordinates' => [
                    [
                        [$lon1, $lat1],
                        [$lon1, $lat2],
                        [$lon2, $lat2],
                        [$lon2, $lat1],
                        [$lon1, $lat1],
                    ],
                ],
            ]]],
            "properties.location" => ['$regex' => "2"]
        ];
        $this->getSingleCollection("Ps", "Ps", $complexFilter,
            ["kVoltage", "Voltage", "location", "TypeByTplnr", "d_name", "oTypePs", "transformer"]);
        $this->getSingleCollection("Opory", "Opory", $complexFilter,
            ["NoInLine", "tplnr", "TypeByTplnr", "location", "Voltage", "kVoltage"]);
        $this->getSingleCollection("Lines", "Lines", $complexFilter,
            ["tplnr", "TypeByTplnr", "location", "Voltage", "kVoltage", "type"]);
        $this->getSingleCollection("Ztp", "Ztp", $geoFilter, []);
        $this->getSingleCollection("Lines","Lines", $geoFilter,
                ["tplnr", "TypeByTplnr", "location", "Voltage", "kVoltage","type","d_name", "addition"]);
    }


    private function getSingleCollection($oldName, $newName, $query, $arr)
    {
        $manager = new Manager();
        $coll = new Collection($manager, $this->db, $oldName);
        $size = $coll->count($query);
        //echo $size;
        $skip = 0;
        $limit = 500000;
        echo $newName.":";
        $this->deleteOldData($newName);
        do{
            $data = $coll->find($query,["skip"=>$skip, "limit"=>$limit]);
            $filteredData = $this->filterData($data->toArray(), $arr);
            $this->sendData($filteredData, $newName);
            unset($filteredData);
            unset($data);
            $skip+=$limit;
        }
        while($skip<$size);
        echo "done\n";
    }

    private function logIn()
    {
        $url = $this->url . "/api/auth?username=" . $this->name . "&password=" . $this->pass;
        $ch = curl_init($url);
        $ex = new Exception("Connect failed " . $url);
        if ($ch === false) {
            throw $ex;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $return = curl_exec($ch);
        if($return === false) {
            throw $ex;
        }
        curl_close($ch);
        $tokens = json_decode($return);
        if(!isset($tokens)) {
            throw new Exception($return);
        }
        if (isset($tokens->expire)) {
            $this->ref = $tokens->ref;
            $this->acc = $tokens->acc;
            $this->exp = $tokens->expire;
        } else {
            throw new Exception("Authorization failed");
        }
    }

    private function deleteOldData($name){
        $url = $this->url . "/api/clearCollection&name=".$name;
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->acc));
            $result = curl_exec($ch);
            curl_close($ch);
        }
        catch(\Exception $exc){
            echo "error".$exc->getMessage();
        }
    }

    private function sendData($data, $name)
    {
        $url = $this->url . "/api/receiveMrsksData";
        $data_string = json_encode($data);
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array("data" => $data_string, "name" => $name));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->acc));
            $result = curl_exec($ch);
            curl_close($ch);
        }
        catch(\Exception $exc){
            echo "error".$exc->getMessage();
        }

    }

    private function filterData($coll, $arr)
    {
        foreach ($coll as $key => $item) {
            unset($coll[$key]->_id);
            if (count($arr) > 0) {
                $props = $item->properties;
                $newProps = [];
                for ($i = 0; $i < count($arr); $i++) {
                    $name = $arr[$i];
                    if (array_key_exists($name, $props))
                        $newProps[$name] = $props[$name];
                }
                $coll[$key]->properties = $newProps;
            }
        }
        return $coll;
    }
}