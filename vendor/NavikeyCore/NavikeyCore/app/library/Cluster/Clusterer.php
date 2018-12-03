<?php

declare(strict_types=1);

namespace NavikeyCore\Library\Cluster;

use NavikeyCore\Library\Cluster\GeoMetric;
use Phpml\Clustering\DBSCAN;
use Phpml\Clustering\KMeans;

class Clusterer
{
    /**
    *  return Cluster of objs
    */
    public static function dbscan(\DS\Vector $objs, int $eps, int $minSamp){
        $res = new \DS\Vector();
        $document = [ "type" => "Feature", "properties" => ["count" => 0], "geometry" => [
            "type" => "Point", "coordinates" => [0,0]
        ]];
        $points = array();
        foreach ($objs as $obj) {
            $points[] = $obj["geometry"]["coordinates"];
        }
        $dbscan = new DBSCAN($epsilon = $eps, $minSamples = $minSamp, new GeoMetric());
        $clusters = $dbscan->cluster($points);
        foreach ($clusters as $cluster) {
            $res[] = ["type" => "Feature", "properties" => ["count" => count($cluster)], "geometry" => [
                "type" => "Point", "coordinates" => [
                    $cluster[0][0],$cluster[0][1]
            ]]];
        }
        return $res;
    }

    public static function kmeans(\DS\Vector $objs, int $count){
        $res = new \DS\Vector();
        $document = [ "type" => "Feature", "properties" => ["count" => 0], "geometry" => [
            "type" => "Point", "coordinates" => [0,0]
        ]];
        $points = array();
        foreach ($objs as $obj) {
            $points[] = $obj["geometry"]["coordinates"];
        }
        $kmeans = new Kmeans($count);
        $clusters = $kmeans->cluster($points);
        foreach ($clusters as $cluster) {
            $centroid = array();
            foreach ($cluster as $point) {
                $centroid[0] += $point[0];
                $centroid[1] += $point[1];
            }
            $centroid[0] = round($centroid[0]/count($cluster), 6);
            $centroid[1] = round($centroid[1]/count($cluster), 6);
            $res[] = ["type" => "Feature", "properties" => ["count" => count($cluster)], "geometry" => [
                "type" => "Point", "coordinates" => [
                    $centroid[0],$centroid[1]
            ]]];
        }
        return $res;
    }
}
