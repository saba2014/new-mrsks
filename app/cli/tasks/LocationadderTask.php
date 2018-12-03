<?php

declare(strict_types=1);

use \Phalcon\Cli\Task;
use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class LocationAdderTask extends Task
{

    private $linesColl, $boundsColl, $oporysColl;

    public function mainAction($arg)
    {
        $this->initCollections();
        $lines = $this->findLinesLocation();
        $this->changeOporysProp($lines);
    }

    private function initCollections(): void
    {
        $manager = new Manager();
        $this->linesColl = new Collection($manager, $this->config->database->dbname, 'Lines');
        $this->boundsColl = new Collection($manager, $this->config->database->dbname, 'LineBonds');
        $this->oporysColl = new Collection($manager, $this->config->database->dbname, 'Opory');
        unset($manager);
    }

    private function findLinesLocation(): array
    {
        $coll = $this->linesColl->find(["properties.location" => ['$exists' => true]]);
        return $coll->toArray();
    }

    private function changeOporysProp(array $lines): void
    {
        $count = count($lines);
        $i = 0;
        if($count > 0) {
            echo "*";
        }
        foreach ($lines as $line) {
            $tplnr = $line["properties"]["tplnr"];
            $newOporysTplnr = ($this->boundsColl->find(["line_tplnr" => $tplnr]))->toArray();
            $location = $line["properties"]["location"];
            if (count($newOporysTplnr) > 0) {
                foreach ($newOporysTplnr as $opory) {
                    $tplnr = $opory["opory_tplnr"];
                    $filter = ["properties.tplnr" => $tplnr];
                    $this->oporysColl->updateOne($filter, ['$set' => ["properties.location" => $location]], ["upsert" => true]);
                }
            }
            if($i >= $count / 10) {
                $i = 0;
                echo "*";
            }
            $i++;
        }
        echo "*\ncomplete\n";
    }
}