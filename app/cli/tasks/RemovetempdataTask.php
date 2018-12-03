<?php
declare(strict_types=1);

use \Phalcon\Cli\Task;
use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;

/**
 * Class RemovetempdataTask
 * Removetempdata main Tokens properties.expireDate 1 unix
 * Removetempdata main Track properties.time 30 utc
 */
class RemovetempdataTask extends Task
{
    private $fieldName, $collName, $period, $deleteTime;

    public function mainAction($arg)
    {
        $this->initArgs($arg);
        $this->removeDocs();
    }

    private function removeDocs()
    {
        $manager = new Manager();
        $coll = new Collection($manager, $this->config->database->dbname, $this->collName);
        try {
            $coll->deleteMany([$this->fieldName => ['$lt' => $this->deleteTime]]);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    private function initArgs(array $arg)
    {

        if (array_key_exists(0, $arg) && isset($arg[0])) {
            $this->collName = $arg[0];
        } else {
            $this->collName = "Tokens";
        }

        if (array_key_exists(1, $arg) && isset($arg[1])) {
            $this->fieldName = $arg[1];
        } else {
            $this->fieldName = "properties.expireDate";
        }
        if (array_key_exists(2, $arg) && isset($arg[2])) {
            $this->period = $arg[2];
        } else {
            $this->period = 1;
        }

        $this->period = time() - $this->period * 24 * 3600;
        if (array_key_exists(3, $arg) && isset($arg[3])) {
            if (!strcmp("utc", $arg[3])) {
                $this->deleteTime = date(DATE_ATOM, $this->period);
            }
            if (!strcmp("unix", $arg[3])) {
                $this->deleteTime = $this->period;
            }
        }


    }
}