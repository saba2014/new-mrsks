<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

use Ds\Map;
use Ds\Vector;
use NavikeyCore\Library\ArgsMaker;

class_alias('NavikeyCore\Library\LayerElectric', 'NavikeyCore\Library\LayerOpory');
class_alias('NavikeyCore\Library\Layer', 'NavikeyCore\Library\LayerUniversRegions');
class_alias('NavikeyCore\Library\LayerLines', 'NavikeyCore\Library\LayerUniversLines');
class_alias('NavikeyCore\Library\LayerPs', 'NavikeyCore\Library\LayerUniversPs');
class_alias('NavikeyCore\Library\LayerElectric', 'NavikeyCore\Library\LayerLineBonds');

class GetObj
{

    private $obj, $db, $string_arg, $float_arg, $status, $option;
    public $status_message;

    public function __construct(string $db, Vector $string_args, Vector $float_args, Vector $layers, $option = [])
    {
        $this->db = $db;
        $this->string_arg = $string_args;
        $this->float_arg = $float_args;
        $this->layers = $layers;
        $this->option = $option;
        $this->status = new StatusPage();
    }

    public function __destruct()
    {
        unset($this->string_arg, $this->float_arg, $this->status);
    }

    public function get(array $get, Vector &$objs, string $role): bool
    {
        $arg = new Map();

        if (!$this->getArguments($get, $arg)) {
            return false;
        }
        $file = 0;
        $type = "";
        if ($arg->hasKey("type")) {
            $type = $arg["type"];
        }

        if ($arg->hasKey("file")) {
            $file = $arg["file"];
        }
        if (!isset($type)) {
            $type = "";
        }
        if ($file == 1) {
            header('Content-Disposition: attachment; filename="' . $type . '.geojson"');
        }
        $this->setCollection($type, $this->obj);
        if ($arg->hasKey("polygon")) {
            $polygon = 0;
            $polygonInfo = new Vector();
            $this->setCollection($arg["polygon"], $polygon);
            $polygon->getInfo($polygonInfo, $arg, $get);
            if (isset($polygonInfo[0]) && isset($polygonInfo[0]["geometry"]) && isset($polygonInfo[0]["geometry"]["type"]) && !strcmp($polygonInfo[0]["geometry"]["type"], "MultiPolygon")) {
                $arg["geometry"] = $polygonInfo[0]["geometry"]["coordinates"][0][0];
            }
        }
        $this->obj->getInfo($objs, $arg, $get);
        if (!$this->isAllow($role)) {
            $this->status_message = $this->status->getStatusInfo(400, "Bad latitude or longitude");
            return false;
        }
        return true;
    }

    public function getObjs(string &$type, Map &$arg, Vector &$objs): void
    {
        $this->setCollection($type);
        $this->obj->getInfo($objs, $arg);
    }

    public function isAllow(string $role): bool
    {
        return $this->obj->isAllow($role);
    }

    private function setCollection(string &$type, &$new_collection): void
    {
        $class = $this->layers;

        if ($class->find($type) === false) {
            $class_name = "NavikeyCore\Library\Layer";
        } else {
            $class_name = "NavikeyCore\Library\Layer$type";
        }
        $new_collection = new $class_name($this->db, $type, $this->option);
        unset($class);
    }

    private function getArguments(array $get, Map &$arg): bool
    {

        foreach ($this->string_arg as $st_arg) {
            if (array_key_exists($st_arg, $get)) {
                $arg[$st_arg] = $get[$st_arg];
            }
        }

        foreach ($this->float_arg as $f_arg) {
            if (array_key_exists($f_arg, $get)) {
                $arg[$f_arg] = (float)$get[$f_arg];
            }
        }

        if (($arg->hasKey("lon1") && $arg->hasKey("lat1") && $arg->hasKey("lon2") && $arg->hasKey("lat2"))) {
            if (abs($arg["lat1"]) > 90 || abs($arg["lat2"]) > 90 || abs($arg["lon1"]) > 180 ||
                abs($arg["lon2"]) > 180) {
                $this->status_message = $this->status->getStatusInfo(400, "Bad latitude or longitude");
                return false;
            }
        }
        return true;
    }

    public function getObjsCount(array $get, int &$objs, string $role): bool
    {
        $arg = new Map();

        if (!$this->getArguments($get, $arg)) {
            return false;
        }
        $file = 0;
        $type = "";
        if ($arg->hasKey("type")) {
            $type = $arg["type"];
        }

        if ($arg->hasKey("file")) {
            $file = $arg["file"];
        }
        if (!isset($type)) {
            $type = "";
        }
        if ($file == 1) {
            header('Content-Disposition: attachment; filename="' . $type . '.geojson"');
        }
        $this->setCollection($type);
        $this->obj->getObjsCount($objs, $arg, $get);
        if (!$this->isAllow($role)) {
            $this->status_message = $this->status->getStatusInfo(400, "Bad latitude or longitude");
            return false;
        }
        return true;
    }


    public function getSeveralTplnrObject(array $get, Vector &$objs){
        $items = [];
        $options=[];
        $res=[];
        $vector = new Vector();
        $tplnr = json_decode($get['regex']);
        $query = ['properties.tplnr'=>['$in'=>$tplnr]];
        $items=[];
        $items[0] = new LayerPs($this->db, 'Ps');
        $items[2] = new LayerElectric($this->db, 'Opory');
        $items[1] = new LayerLines($this->db, 'Lines');
        foreach ($items as $item){
            $answer[$i] = new Vector();
            $cursor = $item->executeQuery($query, $options);
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
            $answer[$i]->push(...$cursor->toArray());
            $type = $item->type;
            $answer[$i]->apply(function ($document) use ($type) {
                $document["properties"]["type"] = $type;
                return $document;
            });
            $objs->push(...$answer[$i]);
        }
        return true;

    }

    public function getTplnrObjects(array $get, Vector &$objs): bool
    {
        $items = [];
        $items_count = [];
        $query = [];
        $options = [];
        $answer = [];
        $items[0] = new LayerPs($this->db, 'Ps');
        $items[1] = new LayerElectric($this->db, 'Lines');
        $items[2] = new LayerLines($this->db, 'Opory');
        $pos = strpos($get['regex'], '*');
        if ($pos !== false) {
            $query["properties.tplnr"]['$regex'] = str_replace("*", '[0-9a-zA-Z-]*', $get['regex']);
            $query["properties.tplnr"]['$options'] = 'i';
        } else {
            $query["properties.tplnr"] = strtoupper($get['regex']);
        }

        for ($i = 0; $i <= 2; $i++) {
            $items_count[$i] = count($items[$i]->executeQuery($query, $options)->toArray());
        }
        $offset = $get['page'] * $get['count'];
        $remaining_items = $get['count'];
        for ($i = 0; $i <= 2; $i++) {
            $answer[$i] = new Vector();
            if ($items_count[$i] - $offset >= $remaining_items) {
                $options['limit'] = intval($remaining_items);
                $options['skip'] = intval($offset);
                $cursor = $items[$i]->executeQuery($query, $options);
                $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
                $answer[$i]->push(...$cursor->toArray());
                $type = $items[$i]->type;
                $answer[$i]->apply(function ($document) use ($type) {
                    $document["properties"]["type"] = $type;
                    return $document;
                });
                $objs->push(...$answer[$i]);
                return true;
            } else if ($items_count[$i] - $offset > 0) {
                $options['limit'] = intval($items_count[$i] - $offset);
                $options['skip'] = intval($offset);
                $cursor = $items[$i]->executeQuery($query, $options);
                $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
                $answer[$i]->push(...$cursor->toArray());
                $remaining_items = $remaining_items - $items_count[$i] + $offset;
                $offset = 0;
                $type = $items[$i]->type;
                $answer[$i]->apply(function ($document) use ($type) {
                    $document["properties"]["type"] = $type;
                    return $document;
                });
                $objs->push(...$answer[$i]);
                continue;
            } else {
                continue;
            }
        }
        unset($items);
        return true;
    }


    public function getNameObjects(array $get, Vector &$objs): bool ////////////переписать
    {
        $items = [];
        $items_count = [];
        $query = [];
        $options = [];
        $answer = [];
        $items[0] = new LayerPs($this->db, 'Ps');
        $items[1] = new LayerElectric($this->db, 'Opory');
        $items[2] = new LayerLines($this->db, 'Lines');
        $searchArr = ['@','#','$','_','&','+','-','*','"',':',';','!','?','[',']','{','}','~','/','(',')'];
        $replaceArr =['\@','\#','\$','\_','\&','\+','\-','\*','\"','\:','\;','\!','\?','\[','\]','\{','\}','\~','\/','\(','\)'];
        $get['regex'] = str_replace($searchArr,$replaceArr, $get['regex']);
        $query["properties.d_name"]['$regex'] = $get['regex'] . '[0-9a-zA-Z-]*';
        $query["properties.d_name"]['$options'] = 'i';
        for ($i = 0; $i <= 2; $i++)
            $items_count[$i] = $items[$i]->getQueryItemsCount($query);
        $offset = $get['page'] * $get['count'];
        $remaining_items = $get['count'];
        for ($i = 0; $i <= 2; $i++) {
            $answer[$i] = new Vector();
            if ($items_count[$i] - $offset >= $remaining_items) {
                $options['limit'] = intval($remaining_items);
                $options['skip'] = intval($offset);
                $cursor = $items[$i]->executeQuery($query, $options);
                $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
                $answer[$i]->push(...$cursor->toArray());
                $type = $items[$i]->type;
                $answer[$i]->apply(function ($document) use ($type) {
                    $document["properties"]["type"] = $type;
                    return $document;
                });
                $objs->push(...$answer[$i]);
                return true;
            } else if ($items_count[$i] - $offset > 0) {
                $options['limit'] = intval($items_count[$i] - $offset);
                $options['skip'] = intval($offset);
                $cursor = $items[$i]->executeQuery($query, $options);
                $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
                $answer[$i]->push(...$cursor->toArray());
                $remaining_items = $remaining_items - $items_count[$i] + $offset;
                $offset = 0;
                $type = $items[$i]->type;
                $answer[$i]->apply(function ($document) use ($type) {
                    $document["properties"]["type"] = $type;
                    return $document;
                });
                $objs->push(...$answer[$i]);
                continue;
            } else {
                continue;
            }
        }
        unset($items);
        return true;
    }

    public function getTplnrCount(array $get, int &$objs): bool
    {
        $query = [];
        $items[0] = new LayerPs($this->db, 'Ps');
        $items[1] = new LayerElectric($this->db, 'Opory');
        $items[2] = new LayerLines($this->db, 'Lines');
        $pos = strpos($get['regex'], '*');
        if ($pos !== false) {
            $query["properties.tplnr"]['$regex'] = str_replace("*", '[0-9a-zA-Z-]*', $get['regex']);
        } else {
            $query["properties.tplnr"] = $get['regex'];
        }
        for ($i = 0; $i < 2; $i++) {
            $objs += $items[$i]->getQueryItemsCount($query);
        }
        return true;
    }

    public function getNameCount(array $get, int &$objs): bool
    {
        $query = [];
        $items[0] = new LayerPs($this->db, 'Ps');
        $items[1] = new LayerElectric($this->db, 'Opory');
        $items[2] = new LayerLines($this->db, 'Lines');
        strtr($get['regex'], '|', '\|');
        $query["properties.d_name"]['$regex'] = $get['regex'] . '[0-9a-zA-Z-]*';
        for ($i = 0; $i < 2; $i++) {
            $objs += $items[$i]->getQueryItemsCount($query);
        }
        return true;
    }
}
