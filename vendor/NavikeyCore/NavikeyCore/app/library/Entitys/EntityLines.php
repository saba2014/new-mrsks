<?php
declare(strict_types = 1);

namespace NavikeyCore\Library\Entitys;

class EntityLines extends EntityElectric {
    public function __construct(string $type, $voltage, $type_line) {
        parent::__construct($type, $voltage);
        $this->arg["type_line"] = $type_line;
        //$this->arg["no_opory"] = 1;
    }
}