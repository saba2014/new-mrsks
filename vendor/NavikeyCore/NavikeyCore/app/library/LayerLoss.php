<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use Ds\Map;
use Ds\Vector;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class LayerLoss extends Layer {

    private $loss;

    public function __construct(string $dbname, string $collection) {
        $this->collection = $collection;
        $this->model_name = "PS";
        Layer::__construct($dbname, "Ps");
        $this->loss = new Collection($this->manager, $dbname, "Loss");
    }

    public function getInfo(Vector &$info, Map &$arg): void {
        $cursor = $this->getCursor($arg);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        $info = new Vector($cursor->toArray());
        $info->apply(function($document) {
            $document["properties"]["type"] = 'PSLoss';
            $loss = $this->loss->findOne(['properties.tplnr' => $document["properties"]["tplnr"]], ["sort" => ['properties.unique_key' => -1]]);
            if (isset($loss)) {
                $document["properties"]["loss"] = $loss["properties"];
                $document["properties"]["loss"]["noloss"] = 0;
            } else {
                $document["properties"]["loss"] = ['noloss' => 1, 'unique_key' => 0, 'date' => 0, 'date_ab' => 0
                    , 'fider_input' => 0, 'po_all' => 0, 'po_jur' => 0, 'po_phys' => 0, 'loss_all' => 0, 'loss_all_pr' => 0,
                    'count_askue_jur' => 0, 'count_askue_fis' => 0, 'count_askue_all' => 0, 'count_non_askue' => 0,
                    'count_jur' => 0, 'count_fis' => 0, 'count_all' => 0, 'color' => 'BLUE'];
            }
            return $document;
        });
    }

}
