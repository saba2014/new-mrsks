<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Vector;
use Ds\Map;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class LayerTrack extends Layer
{
    public $delta;
    private $worker;


    public function __construct(string $dbname, string $collection, $option)
    {
        $this->collection = "Track";
        Layer::__construct($dbname, $collection);
        $this->worker = new Collection($this->manager, $dbname, "Workers");
        $this->delta = 60;
        $this->daysRange = 1;
        $this->longRange = 10000;
        if (isset($option->track->lognRange)) {
            $this->longRange = $option->track->lognRange;
        }
        if (isset($option->track->daysRange)) {
            $this->daysRange = $option->track->daysRange;
        }
        if (isset($option->track->longTime)) {
            $this->delta = $option->track->longTime;
        }


    }

    public function getInfo(Vector &$info, Map &$arg): void
    {
        if ($arg->hasKey("deviceId")) {
            $worker = base64_decode($arg["deviceId"]);
        } else {
            return;
        }
        $cursor = $this->getCursorWorker($worker, $arg);
        $worker = $this->worker->findOne(['properties.deviceId' => $worker], ["sort" => ["properties.time" => 1]]);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        $points = 0;
        if ($arg->hasKey("points") && $arg["points"] == 1) {
            $points = 1;
        }
        if (!isset($worker["properties"]["name"])) $worker["properties"]["name"] = 'unknown';
        $this->getSegments($info, $cursor, $worker["properties"]["name"], $points);

    }

    public function getCursorWorker($worker_id, Map &$arg): Cursor
    {
        $day = date('Y-m-d  H:i:s', strtotime('-' . $this->daysRange . ' day'));
        $day = new \MongoDB\BSON\UTCDateTime(strtotime($day));
        $options = [];
        if ($arg->hasKey("limit")) {
            $options["limit"] = (int)$arg["limit"];
        }
        $query = ['properties.deviceId' => $worker_id];
        if ($arg->hasKey("regex") && $arg->hasKey("fieldRegex")) {
            $query[$arg["fieldRegex"]] = ['$regex' => "^{$arg["regex"]}"];
        }
        $worker = $this->worker->findOne($query, ["sort" => ["properties.time" => 1]], $options);
        $track_query = ['deviceId' => $worker["properties"]["deviceId"], "time" => ['$gte' => $day]];
        return $this->model->find($track_query, ["sort" => ["time" => 1], "limit" => 10000],$options);
    }

    /*
     * calc distance in km between 2 points
     */
    private function calcDistance($point1, $point2)
    {
        $R = 6371;
        $lat1 = (float)$point1[0];
        $lat2 = (float)$point2[0];
        $lon1 = (float)$point1[1];
        $lon2 = (float)$point2[1];
        $f1 = deg2rad($lat1);
        $f2 = deg2rad($lat2);
        $Dfi = deg2rad($lat2 - $lat1);
        $Dliambda = deg2rad($lon2 - $lon1);
        $a = sin($Dfi / 2) * sin($Dfi / 2) + cos($f1) * cos($f2) * sin($Dliambda / 2) * sin($Dliambda / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $d = $R * $c;
        return $d;
    }

    private function getSegments(Vector &$info, $cursor, $name, float $points)
    {
        $new_document = null;
        $time = 0;
        $range = 0;
        $oldPoint = [0, 0];
        $i = 0;
        foreach ($cursor as $document) {
            $document["type"] = "Feature";
            if ($i == 0) $oldPoint = [$document["lon"], $document["lat"]];

            $i++;

            $new_time = strtotime($document["time"]->toDateTime()->format('Y-m-d H:i:s'));

            $range = 1000 * $this->calcDistance($oldPoint, [$document["lon"], $document["lat"]]);
            if (isset($new_document) && ($new_time > ($time + $this->delta))) {
                $new_document["properties"]["name"] = $name;
                if (($new_document["geometry"]["coordinates"]->count() < 2) && ($points)) {
                    $new_document["geometry"]["type"] = "Point";
                    $coord = $new_document["geometry"]["coordinates"][0];
                    unset($new_document["geometry"]["coordinates"]);
                    $new_document["geometry"]["coordinates"] = $coord;
                    $info->push($new_document);
                } else {
                    if (!$points) {
                        $info->push($new_document);
                    }
                }
                $new_document = null;
            } else if (isset($new_document) && $range > $this->longRange) {
                $new_document["properties"]["name"] = $name;
                if (($new_document["geometry"]["coordinates"]->count() < 2) && ($points)) {
                    $new_document["geometry"]["type"] = "Point";
                    $coord = $new_document["geometry"]["coordinates"][0];
                    unset($new_document["geometry"]["coordinates"]);
                    $new_document["geometry"]["coordinates"] = $coord;
                    $info->push($new_document);
                } else {
                    if (!$points) {

                        $info->push($new_document);
                    }
                }
                $new_document = null;
            }
            if (isset($new_document)) {
                $new_document["geometry"]["coordinates"]->push([$document["lon"], $document["lat"]]);
            } else {
                $new_document = $document;
                $new_document["geometry"]["type"] = "LineString";
                $tmp = [$document["lon"], $document["lat"]];
                $new_document["geometry"]["coordinates"] = new Vector();
                $new_document["geometry"]["coordinates"]->push($tmp);
            }
            $time = $new_time;
            $oldPoint = [$document["lon"], $document["lat"]];
        }
        if (isset($new_document)) {
            $new_document["properties"]["name"] = $name;
            if ($new_document["geometry"]["coordinates"]->count() < 2) {
                $coord = $new_document["geometry"]["coordinates"][0];
                unset($new_document["geometry"]["coordinates"]);
                $new_document["geometry"]["coordinates"] = $coord;
                $new_document["geometry"]["type"] = "Point";


            } else {

                $info->push($new_document);
            }
        }
    }

}
