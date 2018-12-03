<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

/**
 * Класс обработки запросов
 * @author alex
 */
class HandlerQuery {

    private $path_query, $mutex_file, $cron_query;

    /**
     * Создаёт объект Handler_query
     * @param string $path_query путь к очереди запросов     
     * @param string $mutex_file мьютекс для записи в файл
     */
    public function __construct($path_query, $mutex_file) {
        $this->path_query = $path_query;
        $this->mutex_file = $mutex_file;
        $this->cron_query = new CronQuery();
    }

    /**
     * @$query запрос в url
     * @$path путь к файлу куда будет сохранён результат запроса
     * Импортирует xml из урла в файл
     */
    public function addQuery($path_xml, $path_log) {
        if (!file_exists($path_xml)) {
            $doc = '<?xml version="1.0" encoding="utf-8"?>'
                    . '<data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></data>';
            file_put_contents($path_xml, $doc, LOCK_EX);
        }
        $xmlfile = simplexml_load_file($path_xml);
        $xmls = $xmlfile->query;
        $date = getdate();
        if (sem_acquire($this->mutex_file)) {
            $xml_query = $this->loadXml($this->path_query);
            $error = false;
            foreach ($xmls as $xml) {
                if (($this->cron_query->dateCheack($xml, $date)) && (!$error)) {
                    $error = $this->processingQuery($xml, $xml_query, $path_log);
                }
            }
            if (!$error) {
                $this->saveXml($xml_query, $this->path_query);
            }
            sem_release($this->mutex_file);
        }
    }

    /**
     * Получет запрос из файла очереди и помечает его mypid
     * @param int $pid pid процесса
     * @return null|SimpleXMLElement возвращает элемент из очереди если он есть
     */
    public function getQuery($pid) {
        if (sem_acquire($this->mutex_file)) {
            $xmls = $this->getXml();
            $need_xml = null;
            foreach ($xmls->query as $xml_query) {
                if (!$this->pidCheack((integer) $xml_query->attributes()["pid"])) {
                    $xml_query->attributes()["pid"] = $pid;
                    $need_xml = $xml_query;
                    break;
                }
            }
            if ($need_xml !== null) {
                file_put_contents($this->path_query, $xmls->asXML(), LOCK_EX);
            }
        }
        sem_release($this->mutex_file);
        return $need_xml;
    }

    /**
     * Удаляет запрос из файла очереди если он = mypid
     * @param int $pid pid процесса
     */
    public function removeQuery($pid) {
        if (sem_acquire($this->mutex_file)) {
            $xmls = $this->getXml();
            $need_xml = null;
            foreach ($xmls->query as $xml_query) {
                if ((integer) $xml_query->attributes()["pid"] === $pid) {
                    $need_xml = $xml_query;
                    break;
                }
            }
            if ($need_xml !== null) {
                $dom = dom_import_simplexml($need_xml);
                $dom->parentNode->removeChild($dom);
                file_put_contents($this->path_query, $xmls->asXML(), LOCK_EX);
            }
        }
        sem_release($this->mutex_file);
    }

    /**
     * Загрузка xml файла
     * @return SimpleXMLElement возвращает xml объект
     */
    private function getXml() {
        file_exists($this->path_query) or die('Could not find file ' . $this->path_query);
        return simplexml_load_file($this->path_query);
    }

    /**
     * Проверяет жив ли ещё pid в системе
     * @param integer $pid проверяемый pid
     * @return bool возвращает результат жив pid или нет 
     */
    private function pidCheack($pid) {
        $tmp = false;
        if ($pid > 0) {
            $tmp = exec("ps --no-headers $pid");
        }
        return (bool) $tmp;
    }

    private function processingQuery($xml, $xml_query, $path_log) {
        if (isset($xml->attributes()["operation"])) {
            $operation = (string) $xml->attributes()["operation"];
        } else {
            file_put_contents($path_log, "Ошибка отсутствует operation \n", FILE_APPEND | LOCK_EX);
            return true;
        }
        if (isset($xml->attributes()["query"])) {
            $query = (string) $xml->attributes()["query"];
        } else {
            file_put_contents($path_log, "Ошибка отсутствует query \n", FILE_APPEND | LOCK_EX);
            return true;
        }
        $wait = "0";
        if (isset($xml->attributes()["wait"])) {
            $wait = (string) $xml->attributes()["wait"];
        }
        $dir = "";
        if (isset($xml->attributes()["dir"])) {
            $dir = (string) $xml->attributes()["dir"];
        }
        $this->addChild($xml_query, $operation, $query, $wait, $dir);
    }

    private function addChild($xml_query, string $operation, string $query, string $wait, string $dir) {
        $child = $xml_query->addChild("query");
        $child->addAttribute("operation", $operation);
        $child->addAttribute("query", $query);
        $child->addAttribute("pid", "0");
        $child->addAttribute("wait", $wait);
        $child->addAttribute("dir", $dir);
    }

    private function saveXml($data, $path) {
        $doc = new \DOMDocument();
        $doc->loadXML($data->asXML());
        $doc->formatOutput = true;
        file_put_contents($path, $doc->saveXML(), LOCK_EX);
    }

    private function loadXml($path) {
        $default = '<?xml version="1.0" encoding="utf-8"?><data></data>';
        if (file_exists($path)) {
            $temp = simplexml_load_string(file_get_contents($path));
            if($temp === false) {
                return simplexml_load_string($default);
            }
        }
        return $temp;
    }

}
