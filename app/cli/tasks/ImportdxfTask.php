<?php

declare(strict_types=1);

use Phalcon\Cli\Task;
use Ds\Vector;
use NavikeyCore\Library\Converter\Color;

class ImportdxfTask extends Task
{
    public function mainAction($arg)
    {
        $string_arg = new Vector(["type", "geojson", "file", "tplnr", "type_line", "no_opory", "near",
            "ps", "res_id", "worker_number", "regex", "fieldRegex", "skip", "type_worker", "type_obj", "deviceId", "timeA", "timeB",
            "names", "bukrs", "location", "balance", "balance_name", "TypeByTplnr", "filId", "kapzatr", "polygon", "geometry", "voltage", "balance_name"]);
        $float_arg = new Vector(["lon1", "lat1", "lon2", "lat2", "lon", "lat", "year_0",
            "year_1", "points"]);
        $layers = new Vector(['Opory', 'Lines', 'Ps', 'Loss', 'Ztp', 'Workers', 'Track', 'Region', 'Res',
            'UniversRegions', 'UniversObjs', 'UniversLines', 'UniversPs', 'LineBonds', 'Sap', 'MobileControllers', 'ElectricMeters',
            'MobileControllersTracks', 'PsArea', 'Message']);

        if (count($arg) < 3) {
            echo "Need 3 arguments [input road] [output DXF file] [res id] \n";
            return;
        }
        if (!file_exists($arg[0])) {
            echo "[input road] is no exists \n";
            return;
        }
        $json = json_decode(file_get_contents($arg[0]), true);
        $DXFConverter = new NavikeyCore\Library\Converter\DXFConverter();
        $get_obgs = new NavikeyCore\Library\GetObj($this->config->database->dbname, $string_arg, $float_arg, $layers);
        $objs = new Vector();
        $formatConvertor = new NavikeyCore\Library\Converter\FormatConverter();
        $role = "Master_admin";
        $get = [];
        $get["type"] = "Res";
        $get["res_id"] = $arg[2];
        $get_obgs->get($get, $objs, $role);
        $DXFConverter->DXFCreator->setLayer("RES", Color::BLACK);
        foreach ($objs as $item) {
            $DXFConverter->addElement($item);
        }

        foreach ($json["features"] as $item) {
            if (isset($item["properties"]["place"]) && isset($item["properties"]["name"]) && !strcmp($item["geometry"]["type"], "Point")) {
                $DXFConverter->DXFCreator->setLayer("Text", Color::BLACK);
                $DXFConverter->DXFCreator->addText($item["geometry"]["coordinates"][0], $item["geometry"]["coordinates"][1], 0, $item["properties"]["name"], 0.004);
            }
            if (isset($item["properties"]["highway"]) && (!strcmp($item["geometry"]["type"], "LineString") || !strcmp($item["geometry"]["type"], "MultiPolygon"))) {
                $DXFConverter->DXFCreator->setLayer("highway", Color::YELLOW);
                $DXFConverter->addElement($item);
            }

            if (isset($item["properties"]["boundary"]) && (!strcmp($item["geometry"]["type"], "LineString") || !strcmp($item["geometry"]["type"], "MultiPolygon"))) {
                $DXFConverter->DXFCreator->setLayer("boundary", Color::GRAY);
                $DXFConverter->addElement($item);
            }

            if (isset($item["properties"]["water"]) && (!strcmp($item["geometry"]["type"], "LineString") || !strcmp($item["geometry"]["type"], "MultiPolygon"))) {
                $DXFConverter->DXFCreator->setLayer("water", Color::BLUE);
                $DXFConverter->addElement($item);
            }


            if (isset($item["properties"]["waterway"]) && (!strcmp($item["geometry"]["type"], "LineString") || !strcmp($item["geometry"]["type"], "MultiPolygon"))) {
                $DXFConverter->DXFCreator->setLayer("waterway", Color::BLUE);
                $DXFConverter->addElement($item);
            }

            if (isset($item["properties"]["place"]) && (!strcmp($item["geometry"]["type"], "LineString") || !strcmp($item["geometry"]["type"], "MultiPolygon"))) {
                $DXFConverter->DXFCreator->setLayer("place", Color::LIGHT_GRAY);
                $DXFConverter->addElement($item);
            }
        }

        $get = [];
        $get["type"] = "Ps";
        $get["ps"] = "ТП";
        $get["polygon"] = "Res";
        $get["res_id"] = $arg[2];
        $get["voltage"] = "[6, 10, 20, 27.5, 35, 110]";
        $get["balance_name"] = "Объекты ПАО «МРСК Сибири»";
        $objs = new Vector();
        $get_obgs->get($get, $objs, $role);
        $size = 0.004;
        foreach ($objs as $item) {
            $color = $this->colorConvert($item["properties"]["kVoltage"]);
            $DXFConverter->DXFCreator->setLayer("TP_$color", $color);
            $DXFConverter->DXFCreator->addTriangle($item["geometry"]["coordinates"][0], $item["geometry"]["coordinates"][1], $size);
        }

        $get["ps"] = "РП";
        $objs = new Vector();
        $get_obgs->get($get, $objs, $role);
        foreach ($objs as $item) {
            $color = $this->colorConvert($item["properties"]["kVoltage"]);
            $DXFConverter->DXFCreator->setLayer("RP_$color", $color);
            $DXFConverter->DXFCreator->addSquare($item["geometry"]["coordinates"][0], $item["geometry"]["coordinates"][1], $size);
        }
        $get["ps"] = "Подстанции";
        $objs = new Vector();
        $get_obgs->get($get, $objs, $role);
        foreach ($objs as $item) {
            $color = $this->colorConvert($item["properties"]["kVoltage"]);
            $DXFConverter->DXFCreator->setLayer("PS_$color", $color);
            $DXFConverter->DXFCreator->addCircle($item["geometry"]["coordinates"][0], $item["geometry"]["coordinates"][1], 0, $size);
        }
//user info
        unset($get["balance_name"]);
        $get["voltage"] = "[220, 500]";
        $get["ps"] = "ТП";
        $objs = new Vector();
        $get_obgs->get($get, $objs, $role);
        foreach ($objs as $item) {
            $color = $this->colorConvert($item["properties"]["kVoltage"]);
            $DXFConverter->DXFCreator->setLayer("TP_$color", $color);
            $DXFConverter->DXFCreator->addTriangle($item["geometry"]["coordinates"][0], $item["geometry"]["coordinates"][1], $size);
        }

        $get["ps"] = "РП";
        $objs = new Vector();
        $get_obgs->get($get, $objs, $role);
        foreach ($objs as $item) {
            $color = $this->colorConvert($item["properties"]["kVoltage"]);
            $DXFConverter->DXFCreator->setLayer("RP_$color", $color);
            $DXFConverter->DXFCreator->addSquare($item["geometry"]["coordinates"][0], $item["geometry"]["coordinates"][1], $size);
        }
        $get["ps"] = "Подстанции";
        $objs = new Vector();
        $get_obgs->get($get, $objs, $role);
        foreach ($objs as $item) {
            $color = $this->colorConvert($item["properties"]["kVoltage"]);
            $DXFConverter->DXFCreator->setLayer("PS_$color", $color);
            $DXFConverter->DXFCreator->addCircle($item["geometry"]["coordinates"][0], $item["geometry"]["coordinates"][1], 0, $size);
        }
        $get["voltage"] = "[6, 10, 20, 27.5, 35, 110, 220, 500]";
        $get["type"] = "Lines";
        $get["no_opory"] = 1;
        $objs = new Vector();
        $get_obgs->get($get, $objs, $role);
        foreach ($objs as $item) {
            $color = $this->colorConvert($item["properties"]["kVoltage"]);
            $DXFConverter->DXFCreator->setLayer("Lines_$color", $color);
            $DXFConverter->addElement($item);
        }
        file_put_contents($arg[1], (string)$DXFConverter->DXFCreator);
    }

    private function colorConvert(string $OldColor): int
    {
        $color = ltrim($OldColor, "#");
        $r = base_convert($color[0] . $color[1], 16, 10);
        $g = base_convert($color[2] . $color[3], 16, 10);
        $b = base_convert($color[4] . $color[5], 16, 10);
        return color::rgb($r, $g, $b);
    }
}
