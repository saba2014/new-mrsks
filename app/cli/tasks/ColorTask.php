<?php
declare(strict_types = 1);

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use Phalcon\Cli\Task;

/**
 * Fixed color, voltage in data base
 *
 * @author admin
 */
class ColorTask extends Task {

    public function mainAction() {
        $manager = new Manager();
        $db = $this->config->database->dbname;
        $path = $this->config->mask->maskurl;
        $lines = $manager->executeQuery($db . ".line", new Query([], []));
        $oporys = $manager->executeQuery($db . ".opory", new Query([], []));
        $pss = $manager->executeQuery($db . ".ps", new Query([], []));
        $line = new BulkWrite();
        $opory = new BulkWrite();
        $ps = new BulkWrite();
        $check = new CheckTplnr($path);
        echo "start<br>";
        $this->updateColorOpory($oporys, $check, $opory);        
        $this->updateColorLines($lines, $check, $line);        
        $this->updateColorPS($pss, $check, $ps);
        $this->execute_bulk($opory, $ps, $lines, $manager);
        unset($opory, $line, $ps, $check, $manager);
    }
    
    private function updateColorOpory(array $oporys, CheckTplnr $check, BulkWrite $bulk): void {      
        foreach ($oporys as $opory) {
            $data = [];
            $mask = $check->getMask($opory["properties"]["tplnr"]);
            $voltage = $mask->voltage;
            $colors = $mask->color;
            $data["type"] = "Feature";
            $data["properties"]["NoInLine"] = $opory["properties"]["NoInLine"];
            $data["properties"]["alt"] = $opory["properties"]["alt"];
            $data["properties"]["tplnr"] = $opory["properties"]["tplnr"];
            $type = $check->getType($opory["properties"]["tplnr"]);
            $data["properties"]["TypeByTplnr"] = $type;
            $data["properties"]["kVoltage"] = $colors;
            $data["properties"]["Voltage"] = $voltage;
            $data["geometry"] = [];
            $data["geometry"]["type"] = $opory["geometry"]["type"];
            $data["geometry"]["coordinates"] = $opory["geometry"]["coordinates"];
            $bulk->update(["properties.tplnr" => $data["properties"]["tplnr"]], $data, ["upsert" => true]);
        }
        echo "opory successful<br>";
    }
    
    private function updateColorLines(array $lines, CheckTplnr $check, BulkWrite $bulk): void {      
        foreach ($lines as $line) {
            $data = array();
            $mask = $check->getMask($line["properties"]["tplnr"]);
            $voltage = $mask->voltage;
            $colors = $mask->color;
            $data["type"] = "Feature";
            $data["properties"]["d_name"] = $line["properties"]["d_name"];
            $data["properties"]["id_finish"] = $line["properties"]["id_finish"];
            $data["properties"]["id_start"] = $line["properties"]["id_start"];
            $data["properties"]["balans"] = $line["properties"]["balans"];
            $data["properties"]["LineID"] = $line["properties"]["LineID"];
            $data["properties"]["tplnr"] = $line["properties"]["tplnr"];
            $type = $check->getType($line["properties"]["tplnr"]);
            $data["properties"]["TypeByTplnr"] = $type;
            $data["properties"]["kVoltage"] = $colors;
            $data["properties"]["Voltage"] = $voltage;
            $data["geometry"] = array();
            $data["geometry"]["type"] = $line["geometry"]["type"];
            $data["geometry"]["coordinates"] = $line["geometry"]["coordinates"];
            $bulk->update(array("properties.tplnr" => $data["properties"]["tplnr"]), $data, array("upsert" => true));
        }
        echo "line successful<br>";
    }
    
    private function updateColorPS(array $pss, CheckTplnr $check, BulkWrite $bulk): void {      
        foreach ($pss as $ps) {
            $data = [];
            $mask = $check->getMask($ps["properties"]["tplnr"]);
            $voltage = $mask->voltage;
            $colors = $mask->color;

            $data["type"] = "Feature";
            $data["properties"]["oTypePS"] = $ps["properties"]["oTypePS"];
            $data["properties"]["address"] = $ps["properties"]["address"];
            $data["properties"]["op_resp"] = $ps["properties"]["op_resp"];
            $data["properties"]["balans"] = $ps["properties"]["balans"];
            $data["properties"]["kl_u"] = $ps["properties"]["kl_u"];
            $data["properties"]["d_name"] = $ps["properties"]["d_name"];
            $data["properties"]["id"] = $ps["properties"]["id"];
            $data["properties"]["tplnr"] = $ps["properties"]["tplnr"];
            $type = $check->getType($ps["properties"]["tplnr"]);
            $data["properties"]["TypeByTplnr"] = $type;
            $data["properties"]["kVoltage"] = $colors;
            $data["properties"]["Voltage"] = $voltage;
            $data["geometry"] = [];
            $data["geometry"]["type"] = $ps["geometry"]["type"];
            $data["geometry"]["coordinates"] = $ps["geometry"]["coordinates"];
            $data["line"] = $ps["line"];
            $bulk->update(["properties.tplnr" => $data["properties"]["tplnr"]], $data, ["upsert" => true]);
        }
        echo "ps successful<br>";
    }
    
    private function execute_bulk($opory, $ps, $lines, $manager): void {
        $db = $this->config->database->dbname;
        try {
            if ($opory->count()) {
                $manager->executeBulkWrite("$db.opory", $opory);
            }
            if ($ps->count()) {
                $manager->executeBulkWrite("$db.ps", $ps);
            }
            if ($lines->count()) {
                $manager->executeBulkWrite("$db.lines", $lines);
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            echo "<li>     Ошибка записи в базу: " . $e->getMessage() . "<br> Код ошибки: " .
            $e->getCode() . "</li>";
        }
    }
}
