<?php

declare(strict_types=1);

use Phalcon\Cli\Task;
use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class ChangetrackTask extends Task
{
    public function mainAction()
    {
        $db = $this->config->database->dbname;
        $collection = "Track";
        $manager = new Manager();
        $this->tracks = new Collection($manager, $db, $collection);
        //Input your new Collection name like  "TrackNew "
        $this->track2 = new Collection($manager, $db, "TrackNew");
        $this->changeCollection();
    }

    public function changeCollection()
    {

        $var = $this->tracks->find();
        foreach ($var as $item) {

            if (isset($item["properties"]) && !empty($item["properties"])) {
                $properties = $item["properties"];
            } else {
                $properties = [];
            }
            if (isset($item["geometry"]) && !empty($item["geometry"])) {
                $geometry = $item["geometry"];
            } else {
                $geometry = [];
            }
            if (isset($properties["time"]) && !empty($properties["time"])) {
                $d = strtotime($properties["time"]) * 1000;

            } else {
                $d = null;
            }
            $date = new MongoDB\BSON\UTCDateTime($d);

            $this->track2->insertOne(["deviceId" => $properties["deviceId"], "time" => $date, "lon" => $geometry["coordinates"][0], "lat" => $geometry["coordinates"][1]]);
        }
    }
}



