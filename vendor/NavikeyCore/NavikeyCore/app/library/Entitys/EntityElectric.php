<?php

declare(strict_types = 1);

namespace NavikeyCore\Library\Entitys;

class EntityElectric extends EntityLayer {

    public function __construct(string $type, $voltage) {
        parent::__construct($type);
        $this->arg["voltage"] = $voltage;
    }

}
