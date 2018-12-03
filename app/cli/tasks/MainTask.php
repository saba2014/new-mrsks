<?php
declare(strict_types=1);

use Phalcon\Cli\Task;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use MongoDB\Driver\Manager;

class MainTask extends Task
{
    public function mainAction()
    {

        echo "This is the default task and the default action" . PHP_EOL;
    }


}