<?php

declare(strict_types = 1);

namespace NavikeyCore\Library\Converter;

class FormatConverter {

    private $list;

    public function __construct() {
        $this->list = ["geojson", "KML", "MongoDB", "STL", "poly", "DXF"];
    }

    public function __destruct() {
        unset($this->list);
    }

    public function convert(string $format, $objs): string {
        if (!$this->isFormat($format)) {
            return "";
        }
        return $this->$format($objs);
    }

    public function isFormat(string $format): bool {
        return in_array($format, $this->list);
    }

    private function geoJson($objs): string {
        return json_encode(["type" => "FeatureCollection", "features" => $objs], JSON_UNESCAPED_UNICODE);
    }

    private function KML($objs): string {
        $KMLConverter = new KMLConverter();
        $dom = $KMLConverter->ArrayToKML((array)$objs);
        return $dom->saveXML();
    }

    private function MongoDB($objs): string {
        $st = "";
        foreach ($objs as $obj) {
            $st .= json_encode($obj, JSON_UNESCAPED_UNICODE) . ",\n";
        }
        return trim($st, ",\n");
    }

    private function STL($objs): string {
        $STLConverter = new STLConverter("../../srtm/srtm");
        return $STLConverter->ArrayToSTL((array)$objs, 2500, 2500);
    }

    private function poly($objs): string {
        $PolyConverter = new PolyConverter();
        return $PolyConverter->ArrayToPoly((array)$objs);
    }

    private function DXF($objs): string {
        $DXFConverter = new DXFConverter();
        return $DXFConverter->ArrayToDXF((array)$objs);
    }

}
