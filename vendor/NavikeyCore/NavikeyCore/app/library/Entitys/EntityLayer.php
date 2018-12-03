<?php

declare(strict_types = 1);

namespace NavikeyCore\Library\Entitys;

use DS\Map;

class EntityLayer {

    public $arg;

    public function __construct(string $type) {
        $this->arg = new Map();
        $this->arg["type"] = $type;
    }

    public function get_api(): string {
        $api = "getobjs?";
        foreach ($this->arg as $key => $obj) {
            $api .= "$key=$obj&";
        }
        return $api;
    }

}
