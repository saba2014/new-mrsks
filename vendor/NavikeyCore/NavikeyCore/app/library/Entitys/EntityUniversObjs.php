<?php

declare(strict_types = 1);

namespace NavikeyCore\Library\Entitys;

class EntityUniversObjs extends EntityLayer {

    public function __construct(string $type, $type_obj) {
        parent::__construct($type);
        $this->arg["type"] = "UniversObjs";
        $this->arg["type_obj"] = $type_obj;
    }

}
