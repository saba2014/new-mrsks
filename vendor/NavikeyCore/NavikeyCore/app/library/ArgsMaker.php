<?php

namespace NavikeyCore\Library;

use \Phalcon\Logger\Adapter\File as FileAdapter;
use \Ds\Map;
use \Ds\Vector;
use \SimpleXMLElement;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use \MongoDB\Driver\Manager;
use MongoDB\BSON\Regex as Regex;
use MongoDB\BSON\ObjectId;

/*
 * Class which contains popular requests (in queries) to MongoDB
 */

class ArgsMaker
{
    public function __construct()
    {

    }


    /**
     * Check if '$regex' is neccessary.
     *
     * @param object $arg .
     *
     * @return Regex
     */
    public function isContainStar($arg)
    {
        $pos = strpos($arg, '*');
        if ($pos == false) {
            return $arg;
        } else {
            $res = new Regex("^" . str_replace('*', "", $arg));
            return $res;
        }
    }


    /*
     * general args-maker for arrays
     *
     * @param array $arr set of objects to find
     *
     * @return array
     */
    public function addAbstractArgArray(array $arr)
    {
        $result = [];
        foreach ($arr as $item) {
            $obj = $this->isContainStar($item);
            $result[] = $obj;
        }
        return $result;
    }


    /*
     * function which make abstract single request or array of requests
     *
     * @param Map $arg set of argument from POST/GET
     * @param array query set of request for MongoDB
     * @param string $name name of propertie to find in arguments
     * @param string $newname new name of propertie
     *
     * @return void
    */
    public function addAbstractArg(Map &$arg, array &$query, string $name, string $newname)
    {
        if ($arg->hasKey($name)) {
            $arr = json_decode($arg[$name]);
            if (!is_array($arr)) {
                $query[$newname] = $this->isContainStar($arg[$name]);
            } else {
                $searchArray = $this->addAbstractArgArray($arr);
                $query[$newname] = ['$in' => $searchArray];
            }
        }
    }

    /*
     * function which add MongoId to query
     * @param Map $arg set of argument from POST/GET
     * @param array query set of request for MongoDB
     * @param string $name name of propertie to find in arguments
     *
     * @return void
     */
    public function addId(Map &$arg, array &$query, string $name){
        if ($arg->hasKey($name)){
            $query["_id"] = new ObjectID((string)$arg[$name]);
        }
    }

    public function dealWithRegExp(Map &$arg, array &$query){
        if ($arg->hasKey("fieldRegex") && $arg->hasKey("regex")){
            $name = $arg["fieldRegex"];
            $value = $arg["regex"];
            if ($value == "") return;
            $query[$name] = $this->isContainStar($value);
        }
    }


    /*
     * function which makes 'or' search in specific fields
     * @param Map &$arg set of argument from POST/GET
     * @param array &$query set of request for MongoDB
     * @param string $name name of propertie to find in arguments
     * @param array $newNames is set of field where need to search
     *
     * @return void
     */
    public function severalFieldsQuery(Map &$arg, array &$query, string $name, array $newNames){
        if ($arg->hasKey($name)){
            $searchVal = $this->isContainStar($arg[$name]);
            $query['$or']=[];
            foreach ($newNames as $field){
                $query['$or'][]=[$field => $searchVal];
            }
        }
    }

    /*
     * filial in Workers
     */
    public function addFilialArg(Map &$arg, array &$query, string $name, string $newname, Collection $collection)
    {
        if ($arg->hasKey($name)) {
            $arr = json_decode($arg[$name]);
            if (!is_array($arr)) {
                $res = $collection->findOne(["properties.composit_id" => $arg[$name]]);
                if ($res) {
                    $id = $res->_id;
                    $query[$newname] = $id;
                }
            } else {
                $query[$newname] = ['$in' => $arr];
            }
        }
    }

}