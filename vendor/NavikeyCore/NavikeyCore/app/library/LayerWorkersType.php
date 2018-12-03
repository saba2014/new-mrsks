<?php
/**
 * Created by PhpStorm.
 * User: george
 * Date: 18.06.18
 * Time: 13:45
 */

namespace NavikeyCore\Library;

use MongoDB\Driver\Cursor;
use Ds\Map;
use NavikeyCore\Library\ArgsMaker;


class LayerWorkersType extends Layer
{
    private $argsMaker;

    function __construct(string $dbname, string $collection) {
        $this->model_name = "Collection";
        $this->argsMaker = new ArgsMaker();
        Layer::__construct($dbname, $collection);
    }

    public function getCursor(Map &$arg): Cursor {
        $query = [];
        $options = ["limit"=>7000];
        return $this->model->find($query, $options);
    }
}