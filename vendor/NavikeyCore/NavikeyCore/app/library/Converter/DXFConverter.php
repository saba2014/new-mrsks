<?php

declare(strict_types=1);

namespace NavikeyCore\Library\Converter;


class DXFConverter
{
    public $DXFCreator, $currentLayer;

    public function __construct()
    {
        $this->DXFCreator = new DXFCreator();
        $this->currentLayer = 0;
    }

    public function __destruct()
    {
        unset($this->DXFCreator);
    }

    public function clear()
    {
        unset($this->DXFCreator);
        $this->DXFCreator = new DXFCreator();
    }

    public function ArrayToDXF(array $array): string
    {
        $this->currentLayer = 0;
        foreach ($array as $item) {
            $this->DXFCreator->setLayer($item, Color::WHITE);
            $this->currentLayer++;
            $this->addElement($item);
        }
        $buffer = (string)$this->DXFCreator;
        return $buffer;
    }

    public function addElement(array $item, ?int $color = Color::BLACK, ?string $layer = null): void
    {
        /*if (isset($color)) {
            if (!isset($layer)) {
                $layer = $this->currentLayer;
                $this->currentLayer++;
            }
            $this->DXFCreator->setLayer($layer, $color);

        }*/
        switch ($item["geometry"]["type"]) {
            case "Point":
                $this->DXFCreator->addPoint($item["geometry"]["coordinates"][0], $item["geometry"]["coordinates"][1], 0);
                break;
            case "LineString":
                $coordinate = [];
                for ($i = 0; $i < count($item["geometry"]["coordinates"]); $i++) {
                    $coordinate[] = $item["geometry"]["coordinates"][$i][0];
                    $coordinate[] = $item["geometry"]["coordinates"][$i][1];
                }
                $this->DXFCreator->addPolyline3d($coordinate);
                break;
            case "MultiPolygon":
                $coordinate = [];
                for ($i = 1; $i < count($item["geometry"]["coordinates"][0][0]); $i++) {
                    $coordinate[] = $item["geometry"]["coordinates"][0][0][$i][0];
                    $coordinate[] = $item["geometry"]["coordinates"][0][0][$i][1];

                }
                $this->DXFCreator->addPolyline3d($coordinate);
                break;
        }
    }

    public function addText(array $item, string $text, ?int $color = Color::BLACK, ?float $size = null): void
    {
        if (isset($color)) {
            $this->DXFCreator->setLayer($this->currentLayer, $color);
            $this->currentLayer++;
        }
        if (!isset($size)) {
            $size = 10;
        }
        $this->DXFCreator->addText($item["geometry"]["coordinates"][0], $item["geometry"]["coordinates"][1], 0, $text, $size);
    }
}
