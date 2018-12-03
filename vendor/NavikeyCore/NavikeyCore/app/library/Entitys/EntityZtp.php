<?php

declare(strict_types = 1);

namespace NavikeyCore\Library\Entitys;

class EntityZtp extends EntityLayer {

    public function __construct(string $type, $year_0, $year_1) {
        parent::__construct($type);
        $this->arg["year_0"] = $year_0;
        $this->arg["year_1"] = $year_1;
    }

}
