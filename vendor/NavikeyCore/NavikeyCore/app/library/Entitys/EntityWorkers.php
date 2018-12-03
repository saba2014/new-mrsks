<?php

declare(strict_types = 1);

namespace NavikeyCore\Library\Entitys;

class EntityWorkers extends EntityLayer {

    public function __construct(string $type, $type_worker) {
        parent::__construct($type);
        $this->arg["type_worker"] = $type_worker;
    }

}
