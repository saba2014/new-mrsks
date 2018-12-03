<?php
/**
 * Created by PhpStorm.
 * User: george
 * Date: 11.12.17
 * Time: 9:37
 */

namespace NavikeyCore\Library;

use \Phalcon\Logger\Adapter\File as FileAdapter;
use \Ds\Map;
use \Ds\Vector;
use \SimpleXMLElement;

class Checker
{
    public $log, $errorCounter;

    public function __construct(FileAdapter &$logger)
    {
        $this->log = $logger;
        $this->errorCounter = 0;
    }

    public function xmlToArray(SimpleXMLElement $elem)
    {
        return $elem->toArray();
    }

    public function toArrayConverter(SimpleXMLElement $data)
    {
        if (is_a($data[0], "SimpleXMLElement")) {
            $points = [];
            foreach ($data as $point) {
                $obj = [];
                $obj[0] = (double)$point->attributes()['coord_long'];
                $obj[1] = (double)$point->attributes()['coord_lat'];
                $points[] = $obj;
                unset($obj);
            }
            return $points;
        }
        return $data;
    }

    public function isPolygonClosed($points): bool
    {
        if (($points[0][0] === $points[count($points) - 1][0]) &&
            ($points[0][1] === $points[count($points) - 1][1])) {
            return true;
        } else {
            $points[] = $points[0];
            return true;
        }
    }

    public function checkIfRightHanded($data): bool
    {
        $points = $this->toArrayConverter($data);
        if (count($points) > 0 && $this->isPolygonClosed($points)) {
            $sum = 0;
            $n = count($points);
            for ($i = 0; $i < $n - 1; $i++) {
                $sum += ($points[$i + 1][0] - $points[$i][0]) * ($points[$i + 1][1] + $points[$i][1]);
            }
            $sum += ($points[0][0] - $points[$n - 1][0]) * ($points[0][1] + $points[$n - 1][1]);
            if ($sum <= 0) {
                return true;
            } else {
                $rightPoints = [];
                $rightPoints[] = $points[0];
                for($i = count($points) - 2; $i > 0; $i--){
                    $rightPoints[] = $points[$i];
                }
                $rightPoints[] = $points[count($points) - 1];
                return true;
                /*$this->errorCounter++;
                $this->log->error("<li> Полигон не является право-ориентированным </li>");
                return false;
                */
            }
        } else {
            return false;
        }
    }

}