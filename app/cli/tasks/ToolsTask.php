<?php
declare(strict_types=1);

use Phalcon\Cli\Task;
use NavikeyCore\Library\LayerRes;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use MongoDB\Driver\Manager;
use MongoDB\BSON\ObjectID;

class ToolsTask extends Task
{
    public function mainAction()
    {
        echo "This is the default task and the default action" . PHP_EOL;
    }

    //Initiate Layer and call findIntersections function
    public function findResIntersectionsAction()
    {
        echo sprintf("Res intersections started\n");
        $Res = new LayerRes($this->config->database->dbname, 'Res');
        $result = $this->findIntersections($Res);
        echo sprintf("Finished\n");
        return $result;
    }

    /*
        * Function that finds all split multipolygons in polygons then find which
        * one is within another and update corresponding multipolygons
        */
    private function findIntersections($Layer)
    {
        $query = [];
        $options = [];
        $results = [];
        $layerObjects = $Layer->model->find();
        $layerObjects->setTypeMap(['root' => 'array', 'document' => 'array']);
        $layerObjects = $layerObjects->toArray();
        $collection = 'ResPolygons';
        $manager = new Manager();
        $model = new Collection($manager, $this->config->database->dbname, $collection);
        $model->deleteMany(array());
        //  split multipolygons in polygons
        foreach ($layerObjects as $layer) {
            $i = 0;
            $id = $layer['_id']->jsonSerialize()['$oid'];
            foreach ($layer['geometry']['coordinates'] as $polygon) {
                $model->insert(
                    array('properties' => array(
                        'parentId' => $id,
                        'iterator' => $i
                    ),
                        'geometry' => array(
                            'type' => 'Polygon',
                            'coordinates' => $polygon
                        ))
                );
                $i++;
            }
        }
        // find which polygons are within another
        $polygons = $model->find();
        $polygons->setTypeMap(['root' => 'array', 'document' => 'array']);
        $polygons = $polygons->toArray();
        $i = 0;
        foreach ($polygons as $layer) {
            foreach ($layer['geometry']['coordinates'] as $polygon) {
                $parentId = new ObjectID($layer['properties']['parentId']);
                $thisId = $layer['_id'];
                $query = array(
                    'geometry' => array(
                        '$geoWithin' => array(
                            '$geometry' => array(
                                'type' => 'Polygon',
                                'coordinates' => array($polygon)
                            )
                        )
                    ),
                    '_id' => array(
                        '$ne' => $thisId
                    ),
                    'properties.parentId' => array(
                        '$ne' => $parentId
                    )
                );
                $tempLayer = $model->find($query);
                $tempLayer->setTypeMap(['root' => 'array', 'document' => 'array']);
                $tempLayerResults = $tempLayer->toArray();
                // update corresponding multipolygons
                $i++;
                $arr = [];
                unset($query);
                foreach ($tempLayerResults as &$item) {
                    $item['geometry']['coordinates'][0] = array_reverse($item['geometry']['coordinates'][0]);
                    foreach ($item['geometry']['coordinates'] as $tempItem) {
                        array_push($arr, $tempItem);
                    }
                }
                if ($arr !== []) {
                    $query = array('_id' => $parentId);
                    $layerItem = $Layer->model->find($query);
                    $layerItem->setTypeMap(['root' => 'array', 'document' => 'array']);
                    $layerItem = $layerItem->toArray();
                    foreach ($arr as $singlePolygon) {
                        if (in_array($singlePolygon, $layerItem[0]['geometry']['coordinates'][0])) {
                            $index = array_search($singlePolygon, $arr);
                            array_splice($arr, $index, 1);
                        }
                    }
                    array_push($layerItem[0]['geometry']['coordinates'][$layer['properties']['iterator']], ...$arr);
                    $Layer->model->findOneAndReplace($query, $layerItem[0]);
                }
                $arr = [];
            }
        }
        return 0;
    }

    // works only for multipolygons
    // validate multipolygon and insert it in db Res
    public function validateJsonAction($arg)
    {
        $url = $arg[0];
        $file = file_get_contents($url);
        $json = json_decode($file, true);
        try {
            if ($json['type'] === 'Feature') {
                if ($json['geometry']['type'] === 'MultiPolygon') {
                    foreach ($json['geometry']['coordinates'] as &$polygon) {
                        if (!$isRight = $this->checkPolygonOrientation($polygon[0])) {
                            //checks for orientation of first element
                            $polygon[0] = array_reverse($polygon[0]);
                        }
                        //checks for intersections of lines, if find one throw exception
                        for ($i = 0; $i < count($polygon[0]) - 2; $i++) {
                            for ($j = 1; $j < count($polygon[0]) - 2; $j++) {
                                if ($j !== $i) {
                                    $intersect = $this->findSelfIntersections($polygon[0][$i], $polygon[0][$i + 1],
                                        $polygon[0][$j], $polygon[0][$j + 1]);
                                    if ($intersect) {
                                        throw new Exception();
                                    }
                                }
                            }
                        }
                        foreach ($polygon as &$area) {
                            $this->arrItemsSwap($area);
                        }
                    }
                }
            }
            $Res = new LayerRes($this->config->database->dbname, 'Res');
            $answer = $Res->model->insert($json);
            echo 'Polygon inserted succesfully';
            return 0;
        } catch (Exception $e) {
            echo 'Polygon have intersections';
            return 1;
        }
    }

    /*
     * return true - clockwise , false counter-clockwise
     */
    private function checkPolygonOrientation(array $arr): bool
    {
        $clockwise = 0;
        for ($i = 0; $i < count($arr); $i++) {
            if ($i != count($arr) - 1) {
                $clockwise += ($arr[$i + 1][0] - $arr[$i][0]) * ($arr[$i + 1][1] + $arr[$i][1]);
            } else {
                $clockwise += ($arr[0][0] - $arr[$i][0]) * ($arr[0][1] + $arr[$i][1]);
            }
        }
        return $clockwise > 0;
    }

    private function arrItemsSwap(array &$arr): void
    {
        for ($i = 0; $i < count($arr); $i++) {
            $temp = $arr[$i][0];
            $arr[$i][0] = $arr[$i][1];
            $arr[$i][1] = $temp;
        }
    }

    /*
     * Знаковая площадь треугольника
     */
    private function area(array $a, array $b, array $c)
    {
        return ($b[0] - $a[0]) * ($c[1] - $a[1]) - ($b[1] - $a[1]) * ($c[0] - $a[0]);
    }

    /*
     * Intersection bb of line segments
     * except the end of first is the start of second line segment
     */
    private function intersect_1($a, $b, $c, $d): bool
    {
        if ($a > $b) {
            $a = $a + $b;
            $b = $a - $b;
            $a = $a - $b;
        }
        if ($c > $d) {
            $c = $c + $d;
            $d = $c - $d;
            $c = $c - $d;
        }
        // $temp=abs(max($a, $c)-min($b, $d)) < 1E-10 ;
        return max($a, $c) < min($b, $d);
    }

    /*
     * Functions checks if line segments intersects
     */
    private function findSelfIntersections($a, $b, $c, $d): bool
    {
        return $this->intersect_1($a[0], $b[0], $c[0], $d[0])
            && $this->intersect_1($a[1], $b[1], $c[1], $d[1])
            && $this->area($a, $b, $c) * $this->area($a, $b, $d) <= 0
            && $this->area($c, $d, $a) * $this->area($c, $d, $b) <= 0;
    }
}