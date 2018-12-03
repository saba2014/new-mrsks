<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use MongoDB\Driver\BulkWrite;

/**
 * Description of Core
 *
 * @author alex
 */
class Line {

    public $data, $key, $error_line, $parent, $tplnr;

    public function __construct() {
        $this->error_line = true;
    }

    public function load(BulkWrite &$mongo_line) {
        if (($this->error_line) && (isset($this->data))) {
            $mongo_line->update(["properties.tplnr" => $this->data["properties"]["tplnr"]], $this->data, ["upsert" => true]);
        }
    }

}
