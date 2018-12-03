<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use \MongoDB\Driver\Cursor;
use \Ds\Map;
use \Ds\Vector;

class LayerMessage extends Layer {

    public $worker_model;

    function __construct(string $dbname, string $collection) {
        $this->model_name = "Collection";
        Layer::__construct($dbname, $collection);
        $this->worker_model = new LayerWorkers($dbname,"Workers");
    }

    public function getInfo(Vector &$info, Map &$arg): void {
        $cursor = $this->getCursor($arg);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        $array = $cursor->toArray();
        foreach ($array as $item) {
            if (isset($item["properties"]) && isset($item["properties"]["time"])) {
                $st = (string) $item["properties"]["time"];
                $item["properties"]["time"] = (int) substr($st, 0, strlen($st) - 3);
                $arg = new Map();
                $worker_cursor = $this->worker_model->getCursorWorkerDeviceId($item["properties"]["deviceId"], $arg);
                $worker_array = $worker_cursor->toArray();
                if(count($worker_array) > 0) {
                    if(isset($worker_array[0]["properties"]["name"])) {
                        $item["properties"]["worker_name"] = $worker_array[0]["properties"]["name"];
                    }
                    if(isset($worker_array[0]["properties"]["info"])) {
                        $item["properties"]["info"] = $worker_array[0]["properties"]["info"];
                    }
                    $item["properties"]["deviceId"] = $worker_array[0]["properties"]["id"];
                }
            }
            $info->push($item);
        }
        //$info->push(...$cursor->toArray());
    }

}
