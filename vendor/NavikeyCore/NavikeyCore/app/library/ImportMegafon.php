<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

/**
 * @author admin
 */
class ImportMegafon {

    private $db, $url;

    public function __construct($db, $url) {
        $this->db = $db;
        $this->url = $url;
    }

    public function importAbonents() {
        $query = "/abonents";
        $items = $this->getData($query);
        $abonents = $items["items"];
        foreach ($abonents as $abonent) {
            $worker = $this->createWorker($abonent);
            if (isset($worker)) {
                $this->db->workers->update(["msisdn" => $worker["msisdn"]], $worker, ["upsert" => true]);
            }
        }
    }

    public function importTrack() {
        $query = "/events/locations";
        $items = $this->getData($query);
        $abonents = $items["items"];
        foreach ($abonents as $abonent) {
            $location = $this->createLocation($abonent);
            if (isset($location)) {
                $this->db->track->update(["time" => $location["time"]], $location, ["upsert" => true]);
            }
        }
    }

    private function getData($query) {
        header("Content-Type: text/html; charset=UTF-8");
        $myCurl = curl_init();
        curl_setopt_array($myCurl, array(
            CURLOPT_URL => $this->url . $query,
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_POST => true,
            //CURLOPT_HTTPGET => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded', 'App-key: 9d2f1f61-374d-41e2-ad60-22f0ec74b7e6'),
            CURLOPT_POSTFIELDS => http_build_query(array())
        ));
        $response = curl_exec($myCurl);
        curl_close($myCurl);
        return json_decode($response, true);
    }

    private function createWorker($abonent) {
        if (!isset($abonent["location"])) {
            return;
        }
        $worker = [];
        $worker["type"] = "Feature";
        $worker["msisdn"] = $abonent["msisdn"];
        $worker["name"] = $abonent["name"];
        $worker["time"] = $abonent["location"]["time"];
        $worker["id"] = $abonent["id"];
        $worker["geometry"] = [];
        $worker["geometry"]["type"] = "Point";
        $worker["geometry"]["coordinates"] = [];
        $worker["geometry"]["coordinates"][0] = $abonent["location"]["lon"];
        $worker["geometry"]["coordinates"][1] = $abonent["location"]["lat"];
        return $worker;
    }

    private function createLocation($abonent) {
        if (!isset($abonent["location"])) {
            return;
        }
        $location = [];
        $location["type"] = "Feature";
        $location["msisdn"] = $abonent["abonent"]["msisdn"];
        $location["name"] = $abonent["abonent"]["name"];
        $location["time"] = $abonent["location"]["time"];
        $location["geometry"] = [];
        $location["geometry"]["type"] = "Point";
        $location["geometry"]["coordinates"] = [];
        $location["geometry"]["coordinates"][0] = $abonent["location"]["lon"];
        $location["geometry"]["coordinates"][1] = $abonent["location"]["lat"];
        return $location;
    }

}
