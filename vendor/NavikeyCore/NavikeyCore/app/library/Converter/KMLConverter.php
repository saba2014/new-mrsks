<?php

declare(strict_types=1);

namespace NavikeyCore\Library\Converter;

use \SimpleXMLElement;

class KMLConverter
{

    public function __construct()
    {

    }

    public function KMLToArray(SimpleXMLElement $simpleXML, $airlayFlag = false): array
    {
        if (!isset($simpleXML->Document)) {
            return [];
        }
        return $this->addChildren($simpleXML->Document, $airlayFlag);
    }

    public function ArrayToKML(array $array, $airlayFlag = false): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $node = $dom->createElementNS('http://www.opengis.net/kml/2.2', 'kml');
        $parNode = $dom->appendChild($node);
        $dnode = $dom->createElement('Document');
        $docNode = $parNode->appendChild($dnode);
        $folder = $dom->createElement('Folder');
        $folderNode = $docNode->appendChild($folder);

        $name = $dom->createElement('name', "objs");
        $folderNode->appendChild($name);
        //$placemark = $dom->createElement('Placemark');
        foreach ($array as $item) {
            $placemark = $dom->createElement('Placemark');
            $name->appendChild($placemark);
            $this->setObject($item, $dom, $folderNode, $placemark, $airlayFlag);
        }
        return $dom;
    }

    private function insertProp($name, $value, &$dom)
    {
        $node = $dom->createElement($name);
        $data = $dom->createTextNode("");
        $connNode = $node->appendChild($data);
        $connNode->appendData((string)$value);
        return $node;
    }

    private function findAndInsertProp($item, &$dom, $propName, $newName)
    {
        if (isset($item[$propName])) {
            $node = $this->insertProp($newName, $item[$propName], $dom);
            return $node;
        } else return false;
    }

    private function insertAddress($item, &$dom, &$propNode)
    {
        if (isset($item["properties"]["additional"])) {
            $addNode = $this->findAndInsertProp($item["properties"]["additional"], $dom, 'nagr_nminus1', 'nagr_nminus1');
            if ($addNode != false)
                $propNode->appendChild($addNode);
            $addNode = $this->findAndInsertProp($item["properties"]["additional"], $dom, 'res_pow_cons_cotr_appl', 'res_pow_cons_cotr_appl');
            if ($addNode != false)
                $propNode->appendChild($addNode);
        }
        $addNode = $this->findAndInsertProp($item["properties"], $dom, 'addr_region', 'addr_region');
        if ($addNode != false)
            $propNode->appendChild($addNode);
        $addNode = $this->findAndInsertProp($item["properties"], $dom, 'addr_district', 'addr_district');
        if ($addNode != false)
            $propNode->appendChild($addNode);
        $addNode = $this->findAndInsertProp($item["properties"], $dom, 'addr_city', 'addr_city');
        if ($addNode != false)
            $propNode->appendChild($addNode);
        $addNode = $this->findAndInsertProp($item["properties"], $dom, 'addr_street', 'addr_street');
        if ($addNode != false)
            $propNode->appendChild($addNode);
        $addNode = $this->findAndInsertProp($item["properties"], $dom, 'addr_house', 'addr_house');
        if ($addNode != false)
            $propNode->appendChild($addNode);
        $addNode = $this->findAndInsertProp($item["properties"], $dom, 'addr_building', 'addr_building');
        if ($addNode != false)
            $propNode->appendChild($addNode);
    }

    private function insertPsProps($item, &$dom, &$folderNode)
    {
        $propNode = $dom->createElement("properties");
        $folderNode->appendChild($propNode);
        $addNode = $this->findAndInsertProp($item["properties"], $dom, 'd_name', 'd_name');
        if ($addNode != false)
            $propNode->appendChild($addNode);
        $addNode = $this->findAndInsertProp($item["properties"], $dom, 'tplnr', 'tplnr');
        if ($addNode != false)
            $propNode->appendChild($addNode);
        $addNode = $this->findAndInsertProp($item["properties"], $dom, 'kl_u', 'kl_u');
        if ($addNode != false)
            $propNode->appendChild($addNode);
        if (isset($item["properties"]['additional'])) {
            $addNode = $this->findAndInsertProp($item["properties"]['additional'], $dom, 'kl_u', 'kl_u');
            if ($addNode != false)
                $propNode->appendChild($addNode);
            if (isset($item["properties"]['additional']['res_pow_cons_cotr_appl']) &&
                isset($item["properties"]['additional']['pow_cotr']) &&
                isset($item["properties"]['additional']['pow_appl'])) {
                $add = $item['properties']['additional'];
                $sum = $add['res_pow_cons_cotr_appl'] + $add['pow_cotr'] + $add['pow_appl'];
                $node = $dom->createElement("res_pow_cons");
                $propNode->appendChild($node);
                $data = $dom->createTextNode("");
                $connNode = $node->appendChild($data);
                $connNode->appendData((string)$sum);
            }
        }
        $this->insertAddress($item, $dom, $propNode);
    }


    private function insertAirLayProps($item, &$dom, &$folderNode)
    {
        $propNode = $dom->createElement("properties");
        $folderNode->appendChild($propNode);
        foreach ($item['properties'] as $key => $value) {
            if (is_numeric($value) || is_string($value)) {
                $addNode = $this->insertProp($key, $value, $dom);
                $propNode->appendChild($addNode);
            }
        }
    }


    private function setObject($item, &$dom, &$folderNode, &$placemark, $airlayFlag)
    {
        $name_geometry = $item["geometry"]["type"];
        $placemarkNode = $folderNode->appendChild($placemark);
        if (!$airlayFlag) {
            if (isset($item["properties"]["type"]) && !strcmp($item["properties"]["type"], "PS"))
                $this->insertPsProps($item, $dom, $placemarkNode);
        } else {
            $this->insertAirLayProps($item, $dom, $placemarkNode);
        }
        $type_geometry = $dom->createElement($name_geometry);
        $type_geometryNode = $placemarkNode->appendChild($type_geometry);
        $geometry = $dom->createElement("coordinates");
        $geometryNode = $type_geometryNode->appendChild($geometry);
        $coordinates = $dom->createTextNode("");
        $coordinatesNode = $geometryNode->appendChild($coordinates);

        if (!strcmp($name_geometry, "LineString")) {
            foreach ($item["geometry"]["coordinates"] as $point) {
                $coordinatesNode->appendData("$point[0],$point[1],0 ");
            }
        }
        if (!strcmp($name_geometry, "MultiPolygon")) {
            foreach ($item["geometry"]["coordinates"][0][0] as $point) {
                $coordinatesNode->appendData("$point[0],$point[1],0 ");
            }
        }
        if (!strcmp($name_geometry, "Point")) {
            foreach ($item["geometry"]["coordinates"] as $point) {
                $coordinatesNode->appendData("$point, ");
            }
            $coordinatesNode->appendData("0");
        }
    }

    private function addChildren($simpleXML, $airLayFlag = false): array
    {
        $result = [];
        foreach ((array)$simpleXML as $key => $item) {
            if (!strcmp("name", (string)$key)) {
                $arrayName = (array)$item;
                $result["name"] = $arrayName[0];
            }
            if (!strcmp(gettype($key), "integer") || !strcmp($key, "Folder")) {
                if (!strcmp(gettype($key), "integer")) {
                    array_push($result, $this->addChildren($item, $airLayFlag));
                } else {
                    $result["Folder"] = $this->addChildren($item, $airLayFlag);
                }
            }
            if (!strcmp((string)$key, "Placemark")) {
                if (!isset($result["Placemark"])) {
                    $result["Placemark"] = [];
                }
                foreach ($item as $point) {
                    if (!$airLayFlag) {
                        array_push($result["Placemark"], $this->addNode($point));
                    } else {
                        array_push($result["Placemark"], $this->addUsualNode($point));
                    }
                }
            }
        }
        return $result;
    }

    private function addNode(SimpleXMLElement $simpleXML): array
    {
        $result = [];
        $result["type"] = "Feature";
        $result["properties"] = [];
        if (isset($simpleXML->name)) {
            $arrayName = (array)$simpleXML->name;
            $result["properties"]["name"] = $arrayName[0];
        }
        if (isset($simpleXML->description)) {
            $result["properties"]["description"] = $simpleXML->description;
        }
        if (isset($simpleXML->Style) && isset($simpleXML->Style->LabelStyle) &&
            isset($simpleXML->Style->LabelStyle->color)) {
            $color = (array)$simpleXML->Style->LabelStyle->color;
            $result["properties"]["color"] = substr($color[0], 0, -2);
        }
        if (isset($simpleXML->Style) && isset($simpleXML->Style->LineStyle) &&
            isset($simpleXML->Style->LineStyle->color)) {
            $color = (array)$simpleXML->Style->LineStyle->color;
            $result["properties"]["color"] = substr($color[0], 0, -2);
        }
        $result["geometry"] = $this->addGeometry($simpleXML);
        return $result;
    }

    /*
     * parse nodes for airlay
     */
    private function addUsualNode(SimpleXMLElement $simpleXML): array
    {
        $result = [];
        $result["type"] = "Feature";
        $result["properties"] = [];
        if (isset($simpleXML->properties)) {
            $result["properties"] = $this->addProperties($simpleXML->properties);
        }
        $result["geometry"] = $this->addGeometry($simpleXML);
        return $result;
    }

    private function addProperties(SimpleXMLElement $simpleXML)
    {
        $arr = array();
        foreach ($simpleXML->children() as $children) {
            if (count($children->children()) == 0) {
                if (strval($children) != null) {
                    $arr[$children->getName()] = strval($children);
                }
            } else {
                $arr[$children->getName()][] = xml2array($children);
            }
        }
        return $arr;
    }

    private function addGeometry(SimpleXMLElement $simpleXML): array
    {
        $result = [];
        if (isset($simpleXML->Point) && isset($simpleXML->Point->coordinates)) {
            $result["type"] = "Point";
            $coordinates = (array)$simpleXML->Point->coordinates;
            $coordinate = explode(",", $coordinates[0]);
            $result["coordinates"] = [(double)$coordinate[0], (double)$coordinate[1]];
        }
        if (isset($simpleXML->LineString) && isset($simpleXML->LineString->coordinates)) {
            $result["type"] = "LineString";
            $result["coordinates"] = $this->addCoordinates($simpleXML->LineString->coordinates);
        }
        return $result;
    }

    private function addCoordinates(SimpleXMLElement $XMLcoordinates): array
    {
        $result = [];
        $array = (array)$XMLcoordinates;
        $coordinates = explode(" ", $array[0]);
        foreach ($coordinates as $point) {
            if (strlen($point) === 0) {
                continue;
            }
            $coordinate = explode(",", $point);
            array_push($result, [(double)$coordinate[0], (double)$coordinate[1]]);
        }
        return $result;
    }

}
