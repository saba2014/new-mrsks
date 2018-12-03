<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

class FTPLoader {

    public $loaded;
    private $conn_id;

    public function __construct() {
        $this->loaded = false;
    }

    public function connectFtp(string $url, $dir) {
        $ftp = parse_url($url);
        $this->conn_id = ftp_connect($ftp["host"]);
        $login_result = ftp_login($this->conn_id, $ftp["user"], $ftp["pass"]);
        if ((!$this->conn_id) || (!$login_result)) {
            throw new \Exception('Не удалось установить соединение с FTP-сервером!');
        }
        ftp_pasv($this->conn_id, true);
        $mes = ftp_chdir($this->conn_id, $dir);
        if (!$mes) {
            throw new \Exception('Нет доступа к папке или она не найдена');
        }
    }

    public function getDataFtp(&$query, &$operation, string $pathFile) {
        $list_path = ftp_nlist($this->conn_id, "");
        if($list_path === false) {
            throw new \Exception('Нет файлов на FTP');
        }
        $this->loaded = true;
        foreach ($list_path as $path) {
            if (!in_array("$path.lock", $list_path) && !in_array(substr($path, 0, strpos($path, ".") - 1) . ".lock", $list_path)) {
                $this->loaded = false;
                break;
            }
            throw new \Exception("Не могу найти файл в списке");
        }
        if ($this->loaded) {
            return 0;
        }
        $size = strpos($path, "_");
        if ($size === false) {
            throw new \Exception('Формат файлов некоректен');
        }
        $operation = substr($path, 0, $size);
        $query = substr($path, $size + 1, strpos($path, ".") - $size - 1);
        if (!ftp_get($this->conn_id, $pathFile . $path, $path, FTP_BINARY)) {
            throw new \Exception('Не могу выкачать файл -> '.$path);
        };
        if(!ftp_delete($this->conn_id, $path)){
            throw new \Exception('Не могу удалить файл -> '.$path);
        }
        ftp_close($this->conn_id);
        return file_get_contents($pathFile . $path);
    }

}
