<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;

function sign($x) {
    if ($x > 0) {
        return 1;
    }
    if ($x < 0) {
        return -1;
    }
    return 0;
}

class UniverseObj {

    private $objs;

    public function __construct(string $db) {
        $manager = new Manager();
        $this->objs = new Collection($manager, $db, "univers_objs");
    }

    public function create($post) {
        $new_obj = [];
        $new_obj['type'] = "Feature";
        $new_obj['properties'] = [];
        $new_obj['properties']['name'] = $post['name'];
        $new_obj['properties']['type'] = $post['group'];
        $new_obj['properties']['info'] = $post['info'];
        if (array_key_exists(display, $post)) {
            $new_obj['properties']['display'] = sign(strcmp($post['display'], "false"));
        } else {
            $new_obj['properties']['display'] = 0;
        }
        $new_obj['geometry'] = [];
        $new_obj['geometry']['type'] = "Point";
        $new_obj['geometry']['coordinates'] = [];
        $new_obj['geometry']['coordinates'][0] = doubleval($post['lat']);
        $new_obj['geometry']['coordinates'][1] = doubleval($post['lon']);
        $this->objs->insertOne($new_obj);
    }

}
