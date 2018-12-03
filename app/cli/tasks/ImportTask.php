<?php

declare(strict_types=1);

use Phalcon\Cli\Task;

class ImportTask extends Task
{

    public function mainAction()
    {
        $import = new NavikeyCore\Library\ImportXml($this->config->mask->maskurl, $this->config->database->dbname,
            $this->config->mask->sap_url, $this->config->mask->query, $this->logger_import);
        $import->addQuery($this->config->mask->import_config, $this->config->log->import);
        $import->import($this->config->mask->path_new_xml,
            $this->config->mask->backup);
    }

    public function legendAction()
    {
        $check = new NavikeyCore\Library\CheckTplnr($this->config->mask->maskurl);
        $check->saveSortMask($this->config->database->dbname, "Lines", "Ps",
            $this->config->mask->path_sort_legend);
    }

    public function universAction()
    {
        $merge = new NavikeyCore\Library\MergeCollections($this->config->database->dbname, $this->config->mask->maskurl,
            $this->logger);
        $merge->Merge("UniversPs", "UniversLines", "univers_electric", "univers_json");
        $check = new NavikeyCore\Library\CheckTplnr($this->config->mask->maskurl);
        $check->saveSortMask($this->config->database->dbname, "UniversLines", "UniversPs",
            $this->config->mask->path_sort_legend_univers);
    }

    public function resLoadAction(array $arg)
    {
        if (!isset($arg[0]) || !file_exists($arg[0])) {
            echo "Need path to file with new Res.\n";
            return;
        }
        $array = json_decode(file_get_contents($arg[0]), true);
        $resModel = new ResModel($this->config->database->dbname);
        foreach ($array["features"] as $feature) {
            $dbRes = $resModel->collection->findOne(["properties.Label" => $feature["properties"]["Label"]]);
            if (!isset($dbRes)) {
                echo $feature["properties"]["Label"] . "\n";
                return;
            }
            $dbRes["geometry"] = $feature["geometry"];
            $resModel->update(["query" => ["properties.Label" => $feature["properties"]["Label"]], "object" =>
                $dbRes]);
        }
        unset($resModel);
    }

    public function importUniverseWaysAction($arg)
    {
        if (!isset($arg[0]) || !file_exists($arg[0])) {
            echo "Need path to file with new Ways.\n";
            return;
        }
        $dirName = $arg[0];
        $fileNames = array_diff(scandir($dirName), array('.', '..'));
        $bulk = new \MongoDB\Driver\BulkWrite();
        foreach ($fileNames as $file) {
            $filename = explode('.json',$file)[0];
            $json = json_decode(file_get_contents($dirName . '/' . $file), true);
            $json['properties']['name'] = $filename;
            $bulk->insert($json);
        }
        $manager = new \MongoDB\Driver\Manager();
        try {
            $manager->executeBulkWrite($this->config->database->dbname . '.' . 'UniverseWays', $bulk);
        } catch (Exception $e) {
            echo 'Error occured trying to write universe ways';
        }
    }

}
