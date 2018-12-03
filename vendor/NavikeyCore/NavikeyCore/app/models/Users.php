<?php

declare(strict_types=1);

namespace NavikeyCore\Models;

use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use Phalcon\Http\Client\Exception;

class Users
{

    private $collection;

    public function __construct(string $dbname)
    {
        $manager = new Manager();
        $this->collection = new Collection($manager, $dbname, "Users");
    }

    public function __destruct()
    {
        unset($this->collection);
    }

    public function findUser(string $type_field, $value_field, $options = [])
    {
        $user = $this->collection->findOne([$type_field => $value_field], $options);
        return $user;
    }

    public function findUserId(string $type_field, $value_field)
    {
        $user = $this->collection->findOne([$type_field => $value_field]);
        return $user["_id"];
    }

    public function checkForExisting($name = "", $email = "")
    {
        $byName = $this->findUser("name", $name);
        $byEmail = $this->findUser("email", $email);
        if ($byName != null || $byEmail != null) {
            return true;
        } else {
            return false;
        }
    }

    public function confirmUser($key)
    {
        $user = $this->collection->findOne(["confirm.key" => $key]);
        if ($user != null) {
            $some = $user->confirm;
            unset($user->confirm);
            $this->collection->deleteOne(["confirm.key" => $key]);
            $this->collection->insertOne($user);
            return true;
        } else return false;
    }

    public function getUser(string $username, string $password)
    {
        if (!isset($password) || !isset($username)) {
            return false;
        }
        $user = $this->collection->findOne(["name" => $username], []);
        if ((!$user) && !isset($user->confirm) && !$user->confirm) {
            return false;
        }
        if (password_verify($password, $user["password"])) {
            return $user;
        } else {
            return false;
        }
    }

    public function createLogPass(string $name = "", string $pass = "", $email = "", string $key = "", array $props = []): bool
    {
        if ((strcmp($name, "") != 0) && !$this->isExsist("name", $name) && (strcmp($pass, "") != 0) && (strcmp($key, ""))) {
            $newUser = [];
            $newUser['name'] = $name;
            $newUser['email'] = $email;
            $newUser['password'] = password_hash($pass, PASSWORD_BCRYPT);
            if (count($props) > 0) {
                $newUser['properties'] = [];
                foreach ($props as $key => $prop) {
                    $newUser['properties'][$key] = $prop;
                }
            }
            $newUser['time'] = time();
            $newUser['confirm'] = [
                'key' => $key
            ];
            $this->collection->insertOne($newUser);
            return true;
        }
        return false;
    }

    public function createLogPassMrsks(string $name = "", string $pass = "", string $role = "Users", array $props = []): bool
    {
        if (strcmp($name, "") || strcmp($pass, "")) {
            $newUser = [];
            $newUser['name'] = $name;
            $newUser['password'] = password_hash($pass, PASSWORD_BCRYPT);
            $newUser['role'] = $role;
            if (count($props) > 0) {
                $newUser['properties'] = [];
                foreach ($props as $key => $prop) {
                    $newUser['properties'][$key] = $prop;
                }
            }
            $this->collection->insertOne($newUser);
            return true;
        }
        return false;
    }

    public function logNamePass(string $name, string $pass): bool
    {
        $new_user = [];
        $new_user["name"] = $name;
        $res = $this->collection->findOne($new_user);
        return (bool)password_verify($pass, $res["password"]);;
    }

    public function createVk(string $vkid): void
    {
        $new_user = [];
        $new_user['vk_id'] = $vkid;
        $new_user['name'] = "user " . date(DATE_RFC822);
        $this->collection->insertOne($new_user);
    }

    private function isExsist(string $fieldType, string $fieldMean): bool
    {
        $new_user = [];
        $new_user[$fieldType] = $fieldMean;
        $res = $this->collection->findOne($new_user);
        return (bool)$res;
    }

    private function randomInt(): string
    {
        $res = uniqid("", true);
        return $res;
    }

    public function generateUnicKey(array $props): string
    {
        $rand = $this->randomInt();
        foreach ($props as $prop) {
            $rand .= $prop;
        }
        $res = md5($rand);
        return $res;
    }

    public function update($query, $data)
    {
        $this->collection->updateOne($query, $data);
    }

    /*
     * return all user layers
     */
    public function getLayers()
    {
        $query = ['name' => $_SESSION['auth']['name']];
        $options = ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $result = $this->collection->findOne($query, $options);
        if ($result) {
            $privateLayers = $result['privateLayers'];
            $publicLayers = $result['publicLayers'];
            $sharedLayers = $result['sharedLayers'];
            $allLayers = array_merge($privateLayers, $sharedLayers, $publicLayers);
            return $allLayers;
        } else {
            return 'Вы не авторизованы для данной операции';
        }
    }

    /*
     * return single user layer which match $id
     */
    public function getLayer($id)
    {
        $query = ['name' => $_SESSION['auth']['name']];
        $options = ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $result = $this->collection->findOne($query, $options);
        if ($result) {
            foreach ($result['privateLayers'] as $layer) {
                if ((string)$layer['_id'] == $id)
                    return $layer;
            }
            foreach ($result['publicLayers'] as $layer) {
                if ((string)$layer['_id'] == $id)
                    return $layer;
            }
            foreach ($result['sharedLayers'] as $layer) {
                if ((string)$layer['_id'] == $id)
                    return $layer;
            }
        } else {
            // throw exception?
            return null;
        }
    }

    public function userHasLayer($id): bool
    {
        $userLayers = $this->getLayers();
        foreach ($userLayers as $layer) {
            if ($layer['_id']->__toString() === $id) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $layerId имя коллекции
     * @return array
     * Возвращает ключи массива properties
     */
    public function getLayerFields($layerId)
    {
        $manager = new Manager();
        $collection = new Collection($manager, 'layers', $layerId);
        $options = ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $userFields = $collection->findOne([], $options);
        $userFields = $userFields['properties'];
        $result = [];
        foreach ($userFields as $key => $field) {
            if (gettype($field) === 'array' || gettype($field) === 'object') {
                unset($userFields[$key]);
                continue;
            }
            array_push($result, $key);
        }
        return $result;
    }


    /**
     * @param $layerId имя коллекции
     * @return array
     * Возвращает установленные в коллекции индексы
     */
    public function getLayersIndexes($layerId)
    {
        $manager = new Manager();
        $collection = new Collection($manager, 'layers', $layerId);
        $indexes = iterator_to_array($collection->listIndexes());
        $response = [];
        for ($i = 0; $i < count($indexes); $i++) {
            if (($indexes[$i]['name'] !== '_id_') && ($indexes[$i]['name'] !== 'geometry_2dsphere')) {
                array_push($response, $indexes[$i]['name']);
            }
        }
        return $response;
    }

    public function ensureLayerIndex($layerId, $index)
    {
        try {
            $manager = new Manager();
            $collection = new Collection($manager, 'layers', $layerId);
            $options['name'] = $index;
            $collection->createIndex(array('properties.' . $index => 1), $options);
            return true;
        } catch (Exception $e) {
            return false;
        }

    }

    public function deleteLayerIndex($layerId, $index)
    {
        try {
            $manager = new Manager();
            $collection = new Collection($manager, 'layers', $layerId);
            $collection->dropIndex($index);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function loadLayerMap($layerId)
    {
        $manager = new Manager();
        $layer = $this->getLayer($layerId);
        return isset($layer['layerMaps']) ? $layer['layerMaps'] : [];
    }


    public function addLayerMap($layerId, $map)
    {
        $query = ['name' => $_SESSION['auth']['name']];
        $options = ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $result = $this->collection->findOne($query, $options);
        //TODO как-нибудь переписать
        foreach ($result['privateLayers'] as &$layer) {
            if ((string)$layer['_id'] === $layerId) {
                $layer['layerMaps'][] = $map;
                $update = ['$set' => ['privateLayers' => $result['privateLayers']]];
            }
        }
        foreach ($result['sharedLayers'] as &$layer) {
            if ((string)$layer['_id'] === $layerId) {
                $layer['layerMaps'][] = $map;
                $update = ['$set' => ['sharedLayers' => $result['sharedLayers']]];
            }
        }
        foreach ($result['publicLayers'] as &$layer) {
            if ((string)$layer['_id'] === $layerId) {
                $layer['layerMaps'][] = $map;
                $update = ['$set' => ['publicLayers' => $result['publicLayers']]];
            }
        }
        $result = $this->collection->findOneAndUpdate($query, $update);
    }

    public function deleteLayerMap($layerId, $map)
    {
        $query = ['name' => $_SESSION['auth']['name']];
        $options = ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $result = $this->collection->findOne($query, $options);
        //TODO как-нибудь переписать
        foreach ($result['privateLayers'] as &$layer) {
            if ((string)$layer['_id'] === $layerId) {
                $index = array_search($map, $layer['layerMaps']);
                array_splice($layer['layerMaps'], $index, 1);
                $update = ['$set' => ['privateLayers' => $result['privateLayers']]];
            }
        }
        foreach ($result['sharedLayers'] as &$layer) {
            if ((string)$layer['_id'] === $layerId) {
                $index = array_search($map, $layer['layerMaps']);
                array_splice($layer['layerMaps'], $index, 1);
                $update = ['$set' => ['sharedLayers' => $result['sharedLayers']]];
            }
        }
        foreach ($result['publicLayers'] as &$layer) {
            if ((string)$layer['_id'] === $layerId) {
                $index = array_search($map, $layer['layerMaps']);
                array_splice($layer['layerMaps'], $index, 1);
                $update = ['$set' => ['publicLayers' => $result['publicLayers']]];
            }
        }
        $result = $this->collection->findOneAndUpdate($query, $update);
    }


    public function getModalInfo($id)
    {
        $layer = $this->getLayer($id);
        return $layer['layerModals'];
    }

    public function updateModalInfo($id, $modalInfo)
    {

    }

    public function createModalInfo($id)
    {
        $manager = new Manager();
        $collection = new Collection($manager, 'layers', $id);
        $options = ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']];
        $layer = $collection->findOne([], $options);
        $layerFields = $layer['properties'];
        $layerModals = $this->recursiveModalGeneration($layerFields);
        // переписать
        $query = ['name' => $_SESSION['auth']['name']];
        $result = $this->collection->findOne($query, $options);
        foreach ($result['privateLayers'] as &$layer) {
            if ((string)$layer['_id'] === $id) {
                $layer['layerModals'] = $layerModals;
                $update = ['$set' => ['privateLayers' => $result['privateLayers']]];
            }
        }
        foreach ($result['sharedLayers'] as &$layer) {
            if ((string)$layer['_id'] === $id) {
                $layer['layerModals'] = $layerModals;
                $update = ['$set' => ['sharedLayers' => $result['sharedLayers']]];
            }
        }
        foreach ($result['publicLayers'] as &$layer) {
            if ((string)$layer['_id'] === $id) {
                $layer['layerModals'] = $layerModals;
                $update = ['$set' => ['publicLayers' => $result['publicLayers']]];
            }
        }
        $result = $this->collection->findOneAndUpdate($query, $update);
        return $layerFields;
    }

    /*
     * takes layer field and return array of it names
     */
    private function recursiveModalGeneration($layerFields)
    {
        $result = [];
        foreach ($layerFields as $name => $layer) {
            if (gettype($layer) === 'array') {
                $result[$name] = ['child' => $this->recursiveModalGeneration($layer), 'name' => $name, 'show' => true, 'realName' => $name];
            } else {
                $result[$name] = ['name' => $name, 'show' => true, 'realName' => $name];
            }
        }
        return $result;
    }

}
