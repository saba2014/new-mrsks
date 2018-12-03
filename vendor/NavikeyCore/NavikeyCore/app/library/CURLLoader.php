<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

class CURLLoader {

    public function __construct() {
        
    }

    /**
     * @$url значение в формате крона
     * return string(mixed) получаем результат запроса 
     * Конфигурация запроса на url и получение данных с этого url
     */
    public function getDataUrl($url) {
        $ch = curl_init();
        $timeout = 30;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 18000);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            throw new \Exception("Ошибка!: $error", curl_errno($ch));
        }
        curl_close($ch);
        return $data;
    }

    public function fileSend(string $url, string $tmpPath, string $fileName) {
        $ch = curl_init($url);

// Create a CURLFile object
        $cfile = new \CURLFile($tmpPath, "application/octet-stream",$fileName);

// Assign POST data
        $data = array($fileName => $cfile);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

// Execute the handle
        $response = curl_exec($ch);
        return $response;
    }

    public function sendRequest(string $url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 2 );
        curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            throw new \Exception("Ошибка!: $error", curl_errno($ch));
        }
        curl_close($ch);
    }
}

