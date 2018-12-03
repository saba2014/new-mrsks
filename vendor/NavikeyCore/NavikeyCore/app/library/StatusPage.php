<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use DS\Map;

class StatusPage {

    private $message;

    public function __construct() {
        $this->message = new Map();
        $this->message[200] = "OK";
        $this->message[400] = "Bad Request";
        $this->message[401] = "Unauthorized";
        $this->message[403] = "Forbidden";
    }

    public function getStatusInfo(int $code, $status_message = null, int $statusCode = 0, array $option = []) {
        if (isset($status_message)) {
            $status = $status_message;
        } else {
            $status = $this->message[$code];
        }
        http_response_code($code);
        $info = $option;

        $info["status"] = $code;
        $info["message"] = $status;
        $info["code"] = $statusCode;
        return json_encode($info);
    }

}
