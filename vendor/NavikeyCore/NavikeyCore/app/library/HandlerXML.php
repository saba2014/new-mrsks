<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

class HandlerXML {

    public $loaded, $error;
    private $conn_id, $log;

    public function __construct($log) {
        $this->loaded = false;
        $this->error = false;
        $this->log = $log;
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
            throw new Exception('Ошибка! ' . $error);
        }
        curl_close($ch);
        return $data;
    }

    public function connectFtp(string $url, $dir) {
        $ftp = parse_url($url);
        $this->conn_id = ftp_connect($ftp["host"]);
        $login_result = ftp_login($this->conn_id, $ftp["user"], $ftp["pass"]);
        if ((!$this->conn_id) || (!$login_result)) {
            $this->log->info("Не удалось установить соединение с FTP-сервером!");
            $this->error = true;
            return;
        }
        $mes = ftp_chdir($this->conn_id, $dir);
        if (!$mes) {
            $this->log->info("Нет доступа к папке или она не найдена");
            $this->error = true;
            return;
        }
        $this->error = false;
    }

    public function getDataFtp(&$query, &$operation) {
        $list_path = ftp_nlist($this->conn_id, "");
        $this->loaded = true;
        foreach ($list_path as $path) {
            if (!in_array("$path.lock", $list_path) && !in_array(substr($path, 0, strpos($path, ".") - 1) . ".lock", $list_path)) {
                $this->loaded = false;
                break;
            }
        }
        if ($this->loaded) {
            return 0;
        }
        $size = strpos($path, "_");
        if ($size === false) {
            $this->error = true;
            $this->log->info("Формат файлов некоректен");
            return 0;
        }
        $operation = substr($path, 0, $size);
        $query = substr($path, $size + 1, strpos($path, ".") - $size - 1);
        ftp_get($this->conn_id, "xmls/$path", $path, FTP_BINARY);
        ftp_delete($this->conn_id, $path);
        return file_get_contents("xmls/$path");
    }

}
