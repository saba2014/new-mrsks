<?php

declare(strict_types=1);

namespace NavikeyCore\Library\Converter;

class STLConverter
{
    private $SRTMReader, $stepLat, $stepLon, $area = [], $lonMin, $latMin, $evalMin, $midlLat;

    public function __construct(string $SRTMpath)
    {
        $this->SRTMReader = new SRTMGeoTIFFReader($SRTMpath);
    }

    public function ArrayToSTL(array $array, int $stepLat, int $stepLon): string
    {
        $buffer = "";
        $this->stepLat = $stepLat;
        $this->stepLon = $stepLon;
        foreach ($array as $item) {
            $buffer .= $this->addElement($item);
        }
        return $buffer;
    }

    private function addElement(array $item): string
    {
        $polygon = $item["geometry"]["coordinates"][0][0];
        $lonMin = 181;
        $lonMax = -181;
        $latMin = 91;
        $latMax = -91;
        foreach ($polygon as $point) {
            if ($point[0] < $lonMin) {
                $lonMin = $point[0];
            }
            if ($point[0] > $lonMax) {
                $lonMax = $point[0];
            }
            if ($point[1] < $latMin) {
                $latMin = $point[1];
            }
            if ($point[1] > $latMax) {
                $latMax = $point[1];
            }
        }
        $normalPolygon = $polygon;
        foreach ($normalPolygon as &$point) {
            $point[0] = (int)(($point[0] - $lonMin) * $this->stepLon);
            $point[1] = (int)(($point[1] - $latMin) * $this->stepLat);
        }
        $this->lonMin = $lonMin;
        $this->latMin = $latMin;
        $this->evalMin = 100000;
        $this->midlLat = (cos($latMax - ($latMax - $latMin) / 2)*40000000)/360;
        $this->binaryFill($normalPolygon);
        return $this->binaryToSTL();
    }

    private function binaryFill(array $points): void
    {
        $miny = $points[0][0];
        $maxy = $points[0][0];
        $count = count($points);
        foreach ($points as $point) {
            if ($point[0] < $miny) {
                $miny = $point[0];
            } else if ($point[0] > $maxy) {
                $maxy = $point[0];
            }
        }

        $midY = ($miny + $maxy) / 2;
        $midX = PHP_INT_MAX;
        $polyInts = array_fill(0, $count, 0);
        for ($y = $miny; $y <= $maxy; $y++) {
            $this->area[] = array_fill(0, $count, 0);
            $ints = 0;
            for ($i = 0; $i < $count - 1; $i++) {
                $p2 = $points[$i];
                $p1 = $points[$i + 1];
                if ($p1[0] < $p2[0]) {
                    $x1 = $p1[1];
                    $y1 = $p1[0];
                    $x2 = $p2[1];
                    $y2 = $p2[0];
                } else if ($p1[0] > $p2[0]) {
                    $x1 = $p2[1];
                    $y1 = $p2[0];
                    $x2 = $p1[1];
                    $y2 = $p1[0];
                } else {
                    continue;
                }
                if ($y >= $y1 && $y < $y2/* || y == maxy && y > y1 && y <= y2*/) {
                    $x = (int)((int)($y - $y1) * ($x2 - $x1) / ($y2 - $y1)) + $x1;
                    if ($x1 > $x2) {
                        --$x;
                    }
                    $polyInts[$ints++] = $x;
                }
            }

            for ($i = 1; $i < $ints; $i++) {
                $index = $polyInts[$i];
                for ($j = $i; $j > 0 && $polyInts[$j - 1] > $index; $j--) {
                    $polyInts[$j] = $polyInts[$j - 1];
                }
                $polyInts[$j] = $index;
            }

            if ($y == $midY && $ints) {
                $bestX1 = 0;
                $bestX2 = 0;
                for ($i = 0; $i < $ints; $i += 2) {
                    $x1 = max($polyInts[$i], 0);
                    $x2 = $polyInts[$i + 1];
                    if ($x2 - $x1 > $bestX2 - $bestX1) {
                        $bestX1 = $x1;
                        $bestX2 = $x2;
                    }
                }
                if ($bestX2 - $bestX1)
                    $midX = ($bestX1 + $bestX2) / 2;
            }

            for ($i = 0; $i < $ints; $i += 2) {
                if ($polyInts[$i] < $polyInts[$i + 1]) {
                    $this->setLine($polyInts[$i], $polyInts[$i + 1], $y);
                }
            }
        }
    }

    private function setLine(int $start, int $finish, int $col)
    {
        for ($i = $start; $i < $finish; $i++) {
            $this->area[$col][$i] = 1;
        }
    }

    private function binaryToSTL(): string
    {
        $arr = $this->area;
        for ($i = 0; $i < count($arr); $i++) {
            for ($j = 0; $j < count($arr[$i]); $j++) {
                if ($arr[$i][$j] === 1) {
                    $arr[$i][$j] = $this->SRTMReader->getElevation($j / $this->stepLat + $this->latMin,
                        $i / $this->stepLon + $this->lonMin, false);
                    if ($arr[$i][$j] < $this->evalMin) {
                        $this->evalMin = $arr[$i][$j];
                    }
                }
            }
        }
        $buffer = "solid RES\n";
        for ($i = 1; $i < count($arr); $i++) {
            for ($j = 0; $j < count($arr[$i]); $j++) {

                if ($arr[$i][$j]) {
                    if (($j > 0) && ($arr[$i - 1][$j - 1]) && ($arr[$i][$j - 1])) {
                        $buffer .= $this->addTriangle([$i, $j, $arr[$i][$j], $i - 1, $j - 1, $arr[$i - 1][$j - 1], $i, $j - 1, $arr[$i][$j - 1]]);
                    }
                    if (($j > 0) && ($arr[$i - 1][$j - 1]) && ($arr[$i - 1][$j])) {
                        $buffer .= $this->addTriangle([$i, $j, $arr[$i][$j], $i - 1, $j, $arr[$i - 1][$j], $i - 1, $j - 1, $arr[$i - 1][$j - 1]]);
                    }
                    if (($j + 1 < count($arr[$i])) && ($arr[$i - 1][$j + 1]) && ($arr[$i - 1][$j]) && ($arr[$i][$j + 1] === 0)) {
                        $buffer .= $this->addTriangle([$i, $j, $arr[$i][$j], $i - 1, $j + 1, $arr[$i - 1][$j + 1], $i - 1, $j, $arr[$i - 1][$j]]);
                    }
                    if (($j + 1 < count($arr[$i])) && ($arr[$i][$j + 1]) && ($arr[$i - 1][$j]) && ($arr[$i - 1][$j + 1] === 0)) {
                        $buffer .= $this->addTriangle([$i, $j, $arr[$i][$j], $i, $j + 1, $arr[$i][$j + 1], $i - 1, $j, $arr[$i - 1][$j]]);
                    }
                    if (($j + 1 < count($arr[$i])) && ($arr[$i][$j + 1]) && ($arr[$i - 1][$j + 1]) && ($arr[$i - 1][$j] === 0)) {
                        $buffer .= $this->addTriangle([$i, $j, $arr[$i][$j], $i, $j + 1, $arr[$i][$j + 1], $i - 1, $j + 1, $arr[$i - 1][$j + 1]]);
                    }
                }

            }
        }
        $buffer .= "endsolid RES\n";
        return $buffer;
    }

    private function addTriangle(array $arr9): string
    {
        $zc = -$this->evalMin;
        $buffer = "facet normal 0 0 1\n";
        $buffer .= "outer loop\n";
        $buffer .= "vertex " . $this->toMetersX($arr9[0]) . " " . $this->toMetersY($arr9[1]) . " " . (string)($arr9[2] + $zc) . "\n";
        $buffer .= "vertex " . $this->toMetersX($arr9[3]) . " " . $this->toMetersY($arr9[4]) . " " . (string)($arr9[5] + $zc) . "\n";
        $buffer .= "vertex " . $this->toMetersX($arr9[6]) . " " . $this->toMetersY($arr9[7]) . " " . (string)($arr9[8] + $zc) . "\n";
        $buffer .= "endloop\n";
        $buffer .= "endfacet\n";

        return $buffer;
    }

    private function toMetersX(int $x)
    {
        $lat = ($x  * $this->midlLat / $this->stepLon);
        return $lat;
    }

    private function toMetersY(int $y)
    {
        $lon = ($y * $this->midlLat / $this->stepLat);
        return $lon;
    }

}
