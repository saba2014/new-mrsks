<?php

use Phalcon\Logger\Formatter;

class emptyFormatter extends Formatter {
    public function format($message, $type, $timestamp, $context = NULL){
        return $message . "\n";
    }
}
