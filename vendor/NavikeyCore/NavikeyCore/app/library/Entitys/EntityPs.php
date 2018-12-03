<?php
declare(strict_types = 1);

namespace NavikeyCore\Library\Entitys;

class EntityPs extends EntityElectric {
    public function __construct(string $type, $voltage, $type_ps) {
        parent::__construct($type, $voltage);
        $this->arg["ps"] = $type_ps;
    }
}
