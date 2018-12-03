<?php

declare(strict_types = 1);

namespace NavikeyCore\Library\Entitys;

class EntityRes extends EntityLayer {

    public function __construct(string $type, $res_id) {
        parent::__construct($type);
        $this->arg["res_id"] = $res_id;
    }

}
