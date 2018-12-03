<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use Phalcon\Logger\Adapter\File as FileAdapter;

/**
 * Description of Core
 *
 * @author alex
 */
class ImportXml {

    private $crud, $backup, $mutex_file, $mypid, $semaphore_query, $count, $handlerQuery,
            $sap_url, $FTPLoader, $CURLLoader, $path_query, $logger;

    public function __construct(string $path_mask, string $db, string $sap_url, string $path_query, FileAdapter &$logger) {
        $this->crud = new ElectricObjectsManager($path_mask, $db, $logger);
        $this->count = 100;
        $this->mutex_file = $this->getSemaphore(dirname(__FILE__) . "/ElectricObjectsManager.php", 1);
        $this->semaphore_query = $this->getSemaphore(dirname(__FILE__) . "/CheckTplnr.php", $this->count);
        $this->semaphore_process = $this->getSemaphore(dirname(__FILE__) . "/CronQuery.php", 10);
        $this->sap_url = $sap_url;
        $this->FTPLoader = new FTPLoader();
        $this->CURLLoader = new CURLLoader();
        $this->handlerQuery = new HandlerQuery($path_query, $this->mutex_file);
        $this->path_query=$path_query;
        $this->construct();
        $this->logger = $logger;
    }
    
    public function __destruct() {
        unset($this->crud, $this->FTPLoader, $this->CURLLoader, $this->handlerQuery);
        //sem_release($this->semaphore_process, $this->mutex_file, $this->semaphore_query);
    }

    public function import($path_new_xml_folder, $path_backup) {
        $xml_query = $this->fork($this->path_query);
        if (!isset($xml_query)) {
            return;
        }
        $st = date(DATE_ATOM) . "   Query start($this->mypid)";
        $resurs = 1;
        if ((int) $xml_query->attributes()["wait"] === 1) {
            $resurs = $this->count;
            $st = $st . " wait = 1";
        }
        $st = $st . "\n";
        echo $st;
        $this->logger->info($st);
        if ($this->lock($this->semaphore_query, $resurs)) {
            $this->importQuery($path_new_xml_folder, $path_backup, $xml_query);
            $this->unlock($this->semaphore_query, $resurs);
        }
        sem_release($this->semaphore_process);
    }

    public function addQuery($path_xml, $path_log) {
        $this->handlerQuery->addQuery($path_xml, $this->path_query, $path_log);
    }

    /**
     * @$query запрос в url
     * @$path путь к файлу куда будет сохранён результат запроса
     * Импортирует xml из урла в файл
     */
    public function importXml($query, $path) {
        try {
            echo date(DATE_ATOM) . "\n";
            $data = $this->CURLLoader->getDataUrl($this->sap_url . $query);
            file_put_contents($path, $data, FILE_APPEND | LOCK_EX);
            echo date(DATE_ATOM) . "\n";
        } catch (\Exception $ex) {
            $this->logger->log("<br/><b>Ошибка загрузки:  {$ex->getMessage()}, код {$ex->getCode()}</b><hr>");
        }
    }

    private function construct() {
        $this->mypid = getmypid();
    }

    private function getSemaphore($path, $count) {
        $key = ftok($path, 'a');
        return sem_get($key, $count, 0666, 1);
    }

    private function importQuery($path_new_xml_folder, $path_backup, $xml_query) {
        $query = $xml_query->attributes()["query"];
        $operation = $xml_query->attributes()["operation"];

        $this->backup = $path_backup;

        $st = date(DATE_ATOM) . "   Script start($this->mypid)  ";

        $url = (string) $query;
        $dir = (string) $xml_query->attributes()["dir"];

        echo $st;
        $this->logger->info($st);
        try {
            if (!strcmp((string) $operation, "ftp")) {
                $this->FTPLoader->connectFtp((string) $query, (string) $xml_query->attributes()["dir"]);
                while ((!$this->FTPLoader->loaded)) {
                    $xml = $this->FTPLoader->getDataFtp($query, $operation, "xmls/");
                    if ($xml) {
                        $this->loadData($path_new_xml_folder, $xml, $query, $operation);
                    }
                    else $this->logger->log("xml файл не получен");
                    $this->FTPLoader->connectFtp($url, $dir);
                }
            } else {
                $xml = $this->CURLLoader->getDataUrl($this->sap_url . $query);
                $this->loadData($path_new_xml_folder, $xml, $query, $operation);
            }
        } catch (\Exception $ex) {
            $this->logger->log("Ошибка загрузки:  {$ex->getMessage()}, код {$ex->getCode()}");
        }
        $st = "  " . date(DATE_ATOM) . "  Operation finished($this->mypid) \n";
        echo $st;
        $this->logger->info($st);

        $this->handlerQuery->removeQuery($this->mypid);
    }

    /**
     * @$query xml запрос
     * @$result результат обработки запроса
     * return string имя созданного лога
     * Сохраняет лог в файл
     */
    private function saveLog($query, $result) {
        $logfilename = $this->generatePath("log/logfile-$query-", "html");
        $tResult = "<html>\n<meta charset=\"UTF-8\" />\n<body>\n" . $result . "</body>\n</html>\n";
        file_put_contents($logfilename, $tResult);
        return $logfilename;
    }

    private function operationLog($path, $query, $operation) {
        $log_file_xml = "Get file xml = <a href='../$path'>$path</a>  ";
        echo $log_file_xml;
        $this->logger->info($log_file_xml);

        $log_operation = "Operation : $operation, Type : $query ";
        echo $log_operation;
        $this->logger->info($log_operation);
    }

    /**
     * @$path_new_xml путь к создоваемому xml файлу
     * @$path_sort_mask путь к маскам tplnr
     * @$query обробатываемый запрос
     * @$operation обробатываемая операция update/delete
     * Проводит загрузку данных, начальное логирование и оброботку исключений
     */
    private function loadData($path_new_xml_folder, $xml, $query, $operation) {
        $path_new_xml = $this->generatePath($path_new_xml_folder, "xml");
        file_put_contents($path_new_xml, "", FILE_APPEND | LOCK_EX);
        $this->operationLog($path_new_xml, $query, $operation);
        file_put_contents($path_new_xml, $xml, FILE_APPEND | LOCK_EX);

        libxml_use_internal_errors(true);
        try {
            $this->tryLoadData($xml, $query, (string)$operation);
        } catch (\Exception $e) {
            $this->cathLoadData($e);
        }
    }

    /**
     * @$path_new_xml путь к создоваемому xml файлу
     * @$path_sort_mask путь к маскам tplnr
     * @$query обробатываемый запрос
     * @$operation обробатываемая операция update/delete
     * Обробатывает загруску данных из url
     */
    private function tryLoadData($xml, $query, string $operation) {
        $data = new \SimpleXMLElement($xml);
        if (!strcmp($operation, "update")) {
            $log_name = $this->generatePath("log/logfile-$query-", "html");
            $this->logger->info("<a href='../$log_name'>$log_name</a>");

            $saveLogger = $this->logger;
            $this->logger = new FileAdapter($log_name);
            $formatter = new \NavikeyCore\Plugins\EmptyFormatter();
            $this->logger->setFormatter($formatter);
            $this->logger->log("<html>\n<meta charset=\"UTF-8\" />\n<body>\n");
            $this->crud->update($data,$this->logger);
            $this->logger->log("</body>\n</html>\n");
            $this->logger = $saveLogger;

        }
        if (!strcmp($operation, "delete")) {
            $backup_name = $this->generatePath($this->backup, "txt");
            $this->crud->delete($data, $backup_name);
            $this->logger->info("<a href='../$backup_name'>$backup_name</a>");
        }
    }

    /**
     * @$e полученое исключение
     * Обробатывает исключение и выводит в log
     */
    private function cathLoadData(\Exception $e) {
        $st = "{ " . $e->getMessage();
        $this->logger->info($st);
        echo $st;
        $errors = libxml_get_errors();
        $st = "  count error = " . count($errors);

        foreach ($errors as $error) {
            $st = $st . " code= " . $error->code . "  " . trim($error->message) .
                    "  Line: $error->line " .
                    "  Column: $error->column;";
        }
        $st = $st . "}";
        $this->logger->info($st);
        echo $st;
    }

    /**
     * @$semaphore симофор который нужно разблокировать
     * @$time время ожидания
     * @$resurs integer количество ресурсов 
     * return bool если ресуры удалось заблокировать
     * Блокирует ресурсы симофора
     */
    private function lock($semaphore, $resurs) {
        for ($i = 0; $i < $resurs; $i++) {
            if (!sem_acquire($semaphore)) {
                unlock($semaphore, $i);
                return false;
            }
        }
        return true;
    }

    /**
     * @$semaphore симофор который нужно разблокировать
     * @$resurs integer количество ресурсов 
     * return bool если ресуры удалось разблокировать
     * Разблокирует ресурсы симофора
     */
    private function unlock($semaphore, $resurs) {
        for ($i = 0; $i < $resurs; $i++) {
            sem_release($semaphore);
        }
        return true;
    }

    /**
     * @$path_query путь к запросу
     * return $xml_query; возвращает xml запрос из очереди 
     * Создаёт параллельных потомков в цикле и получает xml запросы пока очередь не пуста
     */
    private function fork() {
        while (true) {
            if (sem_acquire($this->semaphore_process)) {
                $xml_query = $this->handlerQuery->getQuery($this->mypid);
                if ($xml_query === null) {
                    return;
                }
                $pid = pcntl_fork();
                $this->construct();
                if ($pid == -1) {
                    die('could not fork');
                } else if ($pid) {
                    return $xml_query;
                }
            }
        }
    }

    private function generatePath($name, $type) {
        $path = $name . date('YmdHis') . ".$type";
        $i = 0;
        while (file_exists($path)) {
            $i++;
            $path = $name . date('YmdHis') . "_$i.$type";
        }
        return $path;
    }

}
