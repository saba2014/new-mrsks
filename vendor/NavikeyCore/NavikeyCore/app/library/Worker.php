<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class Worker {


    private $wrokers, $track;

    public function __construct(string $db) {
        $manager = new Manager();
        $this->wrokers = new Collection($manager, $db, "Workers");
        $this->track = new Collection($manager, $db, "Track");
    }

    public function createWorker(string $id, string $deviceId, $number = null): void {
        $document = [];
        $document["type"] = "Feature";
        $document["properties"] = [];
        $document["properties"]["id"] = $id;
        $document["properties"]["deviceId"] = $deviceId;
        $document["properties"]["number"] = $id;
        $document["properties"]["type"] = "car";
        $document["properties"]["registration"] = false;
        if (isset($number)) {
            $document["properties"]["number"] = $number;
        }
        $document["geometry"] = [];
        $document["geometry"]["type"] = "Point";
        $document["geometry"]["coordinates"] = [];
        $document["geometry"]["coordinates"][] = 0;
        $document["geometry"]["coordinates"][] = 0;
        $this->wrokers->insert($document);
    }

    public function checkWorker(string $id): bool {
        $document = $this->wrokers->findOne(["properties.deviceId" => $id]);
        return (bool) $document;
    }

    public function updateLocation(string $id, array $points): void {
        $count = count($points);
        $document = $this->wrokers->findOne(["properties.deviceId" => $id]);
        $last = $points[$count - 1];
        $document["properties"]["time"] = $last["time"];
        $document["geometry"]["coordinates"][0] = (double) $last["lon"];
        $document["geometry"]["coordinates"][1] = (double) $last["lat"];
//        $this->wrokers->updateOne(["properties.deviceId" => $id], ['$set' => ["properties.time" => $last["time"], "geometry.coordinates"
//                => [0 => (double) $last["lon"], 1 => (double) $last["lat"]]], "geometry.type" => "Point"], ["upsert" => true]);
        $this->wrokers->updateOne(["properties.deviceId" => $id], ['$set' => $document]);
        $new_points = [];
        foreach ($points as $point) {
            $new_point = [];
            $new_point["type"] = "Feature";
            $new_point["properties"] = [];
            $new_point["properties"]["deviceId"] = $id;
            $new_point["properties"]["time"] = $point["time"];
            $new_point["geometry"] = [];
            $new_point["geometry"]["type"] = "Point";
            $new_point["geometry"]["coordinates"] = [];
            $new_point["geometry"]["coordinates"][0] = (double) $point["lon"];
            $new_point["geometry"]["coordinates"][1] = (double) $point["lat"];
            $new_points[] = $new_point;
        }
        $this->track->insertMany($new_points);
    }

    public function updateMessage(string $id, int $status): void {
        switch ($status) {
            case 0:
                $message = "delivered";
                break;
            case 1:
                $message = "disconnected";
                break;
            case 2:
                $message = "error";
                break;
            case 3:
                $message = "read";
                break;
        }
        $document = $this->wrokers->findOne(["properties.deviceId" => $id]);
        if(isset($document) && isset($document["properties"]) && isset($document["properties"]["messages"])) {
            for ($i = 0; $i < count($document["properties"]["messages"]); $i++) {
                $document["properties"]["messages"][$i]["status"] = $message;
            }
        }
        $this->wrokers->updateOne(["properties.deviceId" => $id], ['$set' => $document]);
    }




}
