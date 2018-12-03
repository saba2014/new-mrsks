<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

class ModalFactory
{
    private $classList;

    public function __construct(array $classList = [])
    {
        $this->classList = $classList;
    }

    public function __destruct()
    {
        unset($this->classList);
    }

    public function getModal(string $className, string $dbname): \NavikeyCore\Models\Model
    {
        $name = ucfirst($className);
        try {
            return $this->createModal($key, $dbname);
        } catch (Exception $ex) {}
        foreach ($this->classList as $key => $alias) {
            foreach ($alias as $className) {
                if (!strcmp($name, $className)) {
                    try {
                        return $this->createModal($key, $dbname);
                    } catch (Exception $ex) {
                        throw $ex;
                    }
				}
			}
		}
        throw new Exception("Class is not exists", 200);
    }

	private function createModal(string $prefix, string $dbname): \NavikeyCore\Models\Model {
        if (class_exists("{$prefix}Model")) {
            $className = "{$prefix}Model";
            return new $className($dbname);
        }
        if (class_exists("\NavikeyCore\Models\{$prefix}Model")) {
            $className = "\NavikeyCore\Models\{$prefix}Model";
            return new $className($dbname);
        }
        throw new Exception("Class is not exists", 200);
    }
}
