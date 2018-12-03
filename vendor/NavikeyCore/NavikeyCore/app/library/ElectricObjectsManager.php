<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use \Ds\Map;
use \Ds\Vector;
use function MongoDB\BSON\toJSON;
use \MongoDB\Driver\BulkWrite;
use \MongoDB\Driver\Manager;
use \MongoDB\Driver\Query;
use \Phalcon\Logger\Adapter\File as FileAdapter;

use \Phalcon\Http\Response as Response;

/**
 * Класс CRUD создания, чтения, обновления, удаления электрических объектов
 * @author alex
 */
class ElectricObjectsManager {

    private $check, $lines, $opory, $ps, $db, $line_bonds, $path_mask, $error_count,
            $points, $filename, $del_opory, $manager, $logger, $simpleXMLHandler;

    /**
     * Конструктор ElectricObjectsManager
     * @param string $path_mask Путь к xml маскам tplnr
     * @param string $db Название БД
     * @param Manager $manager Менеджер БД
     */
    public function __construct(string &$path_mask, string &$db, FileAdapter &$logger) {
        $this->points = new Map();
        $this->error_count = 0;
        $this->path_mask = $path_mask;
        $this->check = new CheckTplnr($path_mask);
        $this->lines = new BulkWrite();
        $this->opory = new BulkWrite();
        $this->ps = new BulkWrite();
        $this->line_bonds = new BulkWrite();
        $this->db = $db;
        $this->manager = new Manager();
        $this->logger = $logger;
        $this->simpleXMLHandler = new SimpleXMLHandler();
    }
    
    public function __destruct() {
        unset($this->points, $this->check, $this->lines, $this->opory, $this->ps, $this->line_bonds, $this->manager,
                $this->simpleXMLHandler);
    }

    /**
     * Выполняет запрос используя файл
     * @param string $path_xml Путь к xml файлу
     * @return string Возвращает лог выполненого запроса
     */
    public function updatePath(string &$path_xml): void {
        try {
            $file = $this->simpleXMLHandler->getXml($path_xml);
        } catch (Exception $ex) {
            $this->logger->log("<br/><b>Ошибка загрузки обратитесь к администратору:  {$ex->getMessage()}</b><hr>");
            return;
        }
        $this->update($file,$this->logger);
    }

    /**
     * Выполняет обновление базы данных по xml объекту
     * @param SimpleXMLElement $xml xml объект
     * @return string Возвращает лог выполненого запроса
     */
    public function update(\SimpleXMLElement &$xml, $logger): void {
        $this->points->clear();
        unset($this->points);
        $this->points = new Map();
        $this->error_count = 0;
        $lines = new Lines($this->path_mask, $logger, $this->db);
        $oporys = new Oporys($this->path_mask, $logger, $this->db);
        $ps = new PS($this->path_mask, $logger, $this->db);
        $oporys->load($xml->points, $this->points, $logger);
        $lines->load($xml->lines, $this->points, $logger);
        $ps->load($xml->ps, $this->points, $logger);
        unset($points);
        unset($xml);
        $logger->log("<br/><b> Обработка завершена!</b><hr>");
    }

    /**
     * Выполняет запрос используя файл
     * @param string $path_xml Путь к xml файлу
     * @param string $log_path Путь к файлу логов бэкапа
     * @return string Возвращает лог выполненого запроса
     */
    public function deletePath(string &$path_xml, string &$log_path, $user): void {
        try {
            $file = $this->simpleXMLHandler->getXml($path_xml);
        } catch (Exception $ex) {
            $this->logger->log("<br/><b>Ошибка загрузки обратитесь к администратору:  {$ex->getMessage()}</b><hr>");
            return;
        }
        $this->delete($file, $log_path, $user);
    }

    /**
     * Выполняет удоление используя xml объект
     * @param SimpleXMLElement $xml xml объект
     * @param string $log_path Путь к файлу логов бэкапа
     */
    public function delete(\SimpleXMLElement &$xml, string &$log_path, $user = null): void {
        $this->del_opory = true;
        $this->filename = $log_path;

        $count = $this->del($xml->points, $this->opory, "Opory");
        $count += $this->del($xml->lines, $this->lines, "Line");
        $count += $this->del($xml->ps, $this->ps, "Ps");
        $this->executeBulk();
        if (isset($user) && isset($user['username'])) {
            file_put_contents($this->filename, "Пользователь проводивший действие: {$user['username']}\n", FILE_APPEND | LOCK_EX);
        } else {
            file_put_contents($this->filename, "Автоматическое удаление\n", FILE_APPEND | LOCK_EX);
        }
        file_put_contents($this->filename, "\n Файлов удалено: $count", FILE_APPEND | LOCK_EX);
    }

    /**
     * Выполняет удоление используя несколько объектов tplnr/xml
     * @param Ds/Vector $objects объекты для удаления
     * @param string $type Тип объекта лэп, опора, подстанция
     * @param string $log_path Путь к файлу логов бэкапа
     * @param bool $del_opory Параметр удалять все дочерние опоры если true и оставляет их, в противном случаи
     * @return int Количество удалённых объектов
     */
    public function deleteObj(Vector &$objects, string $type, string $log_path, bool $del_opory, $user = null): int {
        $this->filename = $log_path;
        if (isset($user) && isset($user['name'])) {
            file_put_contents($this->filename, "Пользователь проводивший действие: {$user['name']}\n", FILE_APPEND | LOCK_EX);
        }
        $this->del_opory = $del_opory;
        switch ($type) {
            case "ztp":
                $properties = "doknr";
                break;
            case "workers":
                $properties = "id";
                break;
            default :
                $properties = "tplnr";
                break;
        }
        $bulk = new BulkWrite();
        $count = $this->del($objects, $bulk, $type, $properties);
        $this->executeBulk($bulk, $type);
        file_put_contents($this->filename, "\n Файлов удалено: $count", FILE_APPEND | LOCK_EX);
        return $count;
    }

    /**
     * Выполняет удоление используя несколько объектов tplnr/xml
     * @param Ds/Vector $objects объекты для удаления    
     * @param &$collection_temp Монго bulk
     * @param string $type Тип объекта лэп, опора, подстанция
     * @param string $properties Индификатор объекта     
     * @return int Количество удалённых объектов
     */
    private function del($objects, BulkWrite &$collection_temp, string $type, string $properties = "tplnr") {
        $count = 0;
        foreach ($objects as $object) {
            if (!strcmp(gettype($object), "string")) {
                $tplnr = $object;
            } else {
                $tplnr = mb_strtoupper((string) $object->attributes()["tplnr"], 'UTF-8');
            }
            $count += $this->delObj($tplnr, $collection_temp, $properties, $type);
            if (!strcmp($type, "Lines")) {
                if ($this->del_opory) $count += $this->delOporys($tplnr);
                $this->line_bonds->delete(["line_tplnr" => $tplnr]);
                $this->delChild($tplnr, $type);
            }
            if (!strcmp($type, "Opory")) {
                $this->line_bonds->delete(["opory_tplnr" => $tplnr]);
            }
        }
        return $count;
    }

    /**
     * Удаляет объект по tplnr
     * @param string $tplnr tplnr объекта
     * @param BulkWrite $collection_temp коллекция в которой, храниться объект
     * @param string $properties id объекта (tplnr/doknr)
     * @return int Количество удалённых объектов
     */
    private function delObj(string $tplnr, BulkWrite &$collection_temp, string $properties, string $type): int {
        $count = 0;
        $query_temp = ["properties.$properties" => $tplnr];
        $cursor_temp = $this->manager->executeQuery($this->db . "." . $type, new Query($query_temp, []));
        $cursor_temp->setTypeMap(['root' => 'array', 'document' => 'array']);
        $array = $cursor_temp->toArray();
        if (count($array) > 0) {
            $count = $count + count($array);
            $this->backupCursor($array);
            $collection_temp->delete($query_temp);
        }
        return $count;
    }

    /**
     * Выполняет удоление детей объекта по tplnr
     * @param string $tplnr tplnr родителя
     * return int Количество удалённых объектов   
     */
    private function delChild(string &$tplnr): int {
        $count = 0;
        $query = ["properties.tplnr" => ["\$gt" => "$tplnr" . "-0", "\$lt" => "$tplnr" . "-9"]];
        $cursor = $this->manager->executeQuery($this->db . ".Lines", new Query($query, []));
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        $array = $cursor->toArray();
        foreach ($array as $line) {
            $this->line_bonds->delete(["line_tplnr" => $line["properties"]["tplnr"]]);
        }
        if (count($array) > 0) {
            $count = $count + count($array);
            $this->backupCursor($array);
            $this->lines->delete($query);
        }


        if ($this->del_opory) {
            $query = ["properties.tplnr" => ["\$gt" => $tplnr . "-0", "\$lt" => $tplnr . "-9"]];
            $cursor = $this->manager->executeQuery($this->db . ".Lines", new Query($query, []));
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
            foreach ($cursor as $item) {
                $count += $this->delOporys($item["properties"]["tplnr"]);
            }
            //$this->delOporys($tplnr);
            //$this->checkForLeftOpory($tplnr);
        }
        return $count;
    }

    private function cheakLineBonds(string &$tplnr_opory, string &$tplnr_line) {
        $this->line_bonds->delete(["opory_tplnr" => $tplnr_opory, "line_tplnr" => $tplnr_line]);
        if (!$this->line_bonds->count(["opory_tplnr" => $tplnr_opory])) {
            return true;
        }
        return false;
    }

    private function delOporys(string $tplnr): int {
        $count = 0;
        $cursor = $this->manager->executeQuery($this->db . ".LineBonds", new Query(["line_tplnr" => $tplnr], []));
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        foreach ($cursor as $item)
            //if ($this->checkOporyForExisting($item["opory_tplnr"],$tplnr)==true)
        {
            //$this->checkOporyForExisting($item["opory_tplnr"],$tplnr);
            $count += $this->delObj($item["opory_tplnr"], $this->opory, "tplnr", "Opory");
        }
        return $count;
    }

    /*
     * function returns True if opory has more then one owner, and False otherwise
     * @param strint $tplnr tplnr of opory
     * @return bool
     */

    private function checkOporyForExisting(string $tplnr, string $tplnrBoss): bool{
        $count = 0;
        $query = ["opory_tplnr" => $tplnr, "line_tplnr" => ["\$ne"=> $tplnrBoss]];
        $cursor = $this->manager->executeQuery($this->db . ".LineBonds", new Query($query, []));
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        $arr = $cursor->toArray();
        $count = count($arr);
        if ($count>0) {
            return false;
        }
        return true;
    }

    private function checkForLeftOpory(string $tplnr) : void{
        $query = ["properties.tplnr" => ["\$gt" => $tplnr . "-0"]];
        $cursor = $this->manager->executeQuery($this->db . ".Opory", new Query($query, []));
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);
        foreach ($cursor as $item){
            $cur = $this->manager->executeQuery($this->db . ".LineBonds", new Query(["opory_tplnr" => $item["properties.tplnr"]], []));
            $cur->setTypeMap(['root' => 'array', 'document' => 'array']);
            $arr = $cur->toArray();
            if (count($arr)>0) $this->delObj($item["properties.tplnr"], $this->opory, "tplnr", "Opory");
        }
    }

    /**
     * Выполняет бэкап объектов по курсору
     * @param $cursor Монго итератор  
     */
    private function backupCursor(array &$array): void {
        file_put_contents($this->filename, json_encode($array, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Выполняет массовое изменение в БД
     */
    private function executeBulk(BulkWrite $bulk = null, $name = null): void {
        try {
            if ($this->line_bonds->count()) {
                $this->manager->executeBulkWrite("$this->db.LineBonds", $this->line_bonds);
            }
            if ($this->opory->count()) {
                $this->manager->executeBulkWrite("$this->db.Opory", $this->opory);
            }
            if ($this->ps->count()) {
                $this->manager->executeBulkWrite("$this->db.Ps", $this->ps);
            }
            if ($this->lines->count()) {
                $this->manager->executeBulkWrite("$this->db.Lines", $this->lines);
            }
            if (isset($bulk) && isset($name) && $bulk->count()) {
                $this->manager->executeBulkWrite("$this->db.$name", $bulk);
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            file_put_contents($this->filename, "Ошибка записи в базу: " . $e->getMessage() .
                    "<br> Код ошибки: " . $e->getCode(), FILE_APPEND | LOCK_EX);
//            echo "<li>     Ошибка записи в базу: " . $e->getMessage() . "<br> Код ошибки: " .
//            $e->getCode() . "</li>";
        }
        unset($this->lines, $this->opory, $this->ps, $this->line_bonds);
        $this->lines = new BulkWrite();
        $this->opory = new BulkWrite();
        $this->ps = new BulkWrite();
        $this->line_bonds = new BulkWrite();
    }

}
