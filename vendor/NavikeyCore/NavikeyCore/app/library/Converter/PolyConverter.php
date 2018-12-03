<?php

declare(strict_types=1);

namespace NavikeyCore\Library\Converter;

class PolyConverter
{

    public function __construct()
    {
    }

    public function ArrayToPoly(array $array): string
    {
        $buffer = "Areas\n";
        $i = 0;
        foreach ($array as $item) {
            $buffer .= "Relation $i\n";
            $i++;
            $buffer .= $this->addElement($item);
            $buffer .= "END\n";
        }
        $buffer .= "END\n";
        return $buffer;
    }

    private function addElement(array $item): string
    {
        $buffer = "";
        switch ($item["geometry"]["type"]) {
            case "Point":
                $buffer .= $this->addPoint($item["geometry"]["coordinates"]);
                break;
            case "LineString":
                foreach ($item["geometry"]["coordinates"] as $coordinate) {
                    $buffer .= $this->addPoint($coordinate);
                }
                break;
            case "MultiPolygon":
                foreach ($item["geometry"]["coordinates"][0][0] as $coordinate) {
                    $buffer .= $this->addPoint($coordinate);
                }
                break;
        }
        return $buffer;
    }

    private function addPoint(array $array): string
    {
        return $array[0] . " " . $array[1] . "\n";
    }

}
