<?php

declare(strict_types = 1);

namespace NavikeyCore\Plugins;

use Phalcon\Logger\Formatter;

class EmptyFormatter extends Formatter {

    public function format($message, $type, $timestamp, $context = NULL) {
        return $message . "\n";
    }

}
