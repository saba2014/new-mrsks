<?php

declare(strict_types=1);

namespace NavikeyCore\Library\Converter;


class DXFCreator
{

    // units codes
    const UNITLESS = 0;
    const INCHES = 1;
    const FEET = 2;
    const MILES = 3;
    const MILLIMETERS = 4;
    const CENTIMETERS = 5;
    const METERS = 6;
    const KILOMETERS = 7;
    const MICROINCHES = 8;
    const MILS = 9;
    const YARDS = 10;
    const ANGSTROMS = 11;
    const NANOMETERS = 12;
    const MICRONS = 13;
    const DECIMETERS = 14;
    const DECAMETERS = 15;
    const HECTOMETERS = 16;
    const GIGAMETERS = 17;
    const ASTRONOMICAL_UNITS = 18;
    const LIGHT_YEARS = 19;
    const PARSECS = 20;

    /**
     * @var null Last error description
     */
    private $error = '';

    /**
     * @var array Layers collection
     */
    private $layers = [];

    /**
     * Current layer name
     * @var int
     */
    private $layerName = '0';

    /**
     * @var array Shapes collection
     */
    private $shapes = [];

    /**
     * @var array Center offser
     */
    private $offset = [0, 0, 0];

    /**
     * @var int Units
     */
    private $units = 1;


    /**
     * @param int $units (MILLIMETERS as default value)
     * Create new DXF document
     */
    function __construct($units = self::MILLIMETERS)
    {
        $this->units = $units;
        // add default layout
        $this->addLayer($this->layerName);
    }


    /**
     * Save DXF document to file
     * @param string $fileName
     * @return bool True on success
     */
    function saveToFile($fileName)
    {
        $this->error = '';
        $dir = dirname($fileName);
        if (!is_dir($dir)) {
            $this->error = "Directory not exists: {$dir}";
            return false;
        }
        if (!file_put_contents($fileName, $this->getString())) {
            $this->error = "Error on save: {$fileName}";
            return false;
        }
        return true;
    }


    /**
     * Send DXF document to browser
     * @param string $fileName
     * @param bool $stop Set to FALSE if no need to exit from script
     */
    public function sendAsFile($fileName, $stop = true)
    {
        while (false !== ob_get_clean()) {
        };
        header("Content-Type: image/vnd.dxf");
        header("Content-Disposition: inline; filename={$fileName}");
        echo $this->getString();
        if ($stop) {
            exit;
        }
    }


    /**
     * Returns DXF document as string
     * @return string DXF document
     */
    private function getString()
    {
        return $this->getHeaderString() . $this->getBodyString();
    }


    /**
     * Generates HEADER
     * @return string
     */
    private function getHeaderString()
    {
        $str = "  0\nSECTION\n  2\nHEADER\n  9\n\$ACADVER\n  1\nAC1009\n  9\n$" . "INSUNITS\n 70\n{$this->units}\n  0\nENDSEC\n  0\n";
        //layers
        $str .= "SECTION\n  2\nTABLES\n  0\nTABLE\n  2\n";
        $str .= $this->getLayersString();
        $str .= "ENDTAB\n  0\nENDSEC\n";
        return $str;
    }


    /**
     * Generates BODY
     * @return string
     */
    private function getBodyString()
    {
        $str = "  0\nSECTION\n  2\nENTITIES\n  0\n";
        $str .= implode('', $this->shapes);
        $str .= "ENDSEC\n  0\nEOF\n";
        return $str;
    }


    /**
     * Generates LAYERS
     * @return string
     * @see http://www.autodesk.com/techpubs/autocad/acad2000/dxf/layer_dxf_04.htm
     */
    private function getLayersString()
    {
        $str = "LAYER\n  0\n";
        $count = 1;
        foreach ($this->layers as $name => $layer) {
            $str .= "LAYER\n  2\n{$name}\n 70\n 64\n 62\n {$layer['color']}\n  6\n{$layer['lineType']}\n  0\n";
            $count++;
        }
        return $str;
    }


    /**
     * Returns last error
     * @return null
     */
    public function getError()
    {
        return $this->error;
    }


    /**
     * Set offset
     * @param $x
     * @param $y
     * @param $z
     */
    public function setOffset($x, $y, $z = 0)
    {
        $this->offset = [$x, $y, $z];
    }


    /**
     * Get offset
     * @return array
     */
    public function getOffset()
    {
        return $this->offset;
    }


    /**
     * Add new layer to document
     * @param string $name
     * @param int $color Color code (@see adamasantares\dxf\Color class)
     * @param string $lineType Line type (@see adamasantares\dxf\LineType class)
     * @return Creator Instance
     */
    public function addLayer($name, $color = Color::GRAY, $lineType = LineType::SOLID)
    {
        $this->layers[$name] = [
            'color' => $color,
            'lineType' => $lineType
        ];
        return $this;
    }


    /**
     * Sets current layer for drawing. If layer not exists than it will be created.
     * @param $name
     * @param int $color (optional) Color code. Only for new layer (@see adamasantares\dxf\Color class)
     * @param string $lineType (optional) Only for new layer
     * @return Creator Instance
     */
    public function setLayer($name, $color = Color::GRAY, $lineType = LineType::SOLID)
    {
        if (!isset($this->layers[$name])) {
            $this->addLayer($name, $color, $lineType);
        }
        $this->layerName = $name;
        return $this;
    }


    /**
     * Returns current layer name
     */
    public function getLayer()
    {
        $this->layerName;
    }


    /**
     * Change color for current layer
     * @param int $color See adamasantares\dxf\Color constants
     * @return Creator Instance
     */
    public function setColor($color)
    {
        $this->layers[$this->layerName]['color'] = $color;
        return $this;
    }


    /**
     * Change line type for current layer
     * @param int $lineType See adamasantares\dxf\LineType constants
     * @return Creator Instance
     */
    public function setLineType($lineType)
    {
        $this->layers[$this->layerName]['lineType'] = $lineType;
        return $this;
    }


    /**
     * Add point to current layout
     * @param float $x
     * @param float $y
     * @param float $z
     * @return Creator Instance
     * @see http://www.autodesk.com/techpubs/autocad/acad2000/dxf/point_dxf_06.htm
     */
    public function addPoint($x, $y, $z)
    {
        $x += $this->offset[0];
        $y += $this->offset[1];
        $z += $this->offset[2];
        $this->shapes[] = "POINT\n  8\n{$this->layerName}\n100\nAcDbPoint\n 10\n{$x}\n 20\n{$y}\n 30\n{$z}\n  0\n";
        return $this;
    }


    /**
     * Add line to current layout
     * @param float $x
     * @param float $y
     * @param float $z
     * @param float $x2
     * @param float $y2
     * @param float $z2
     * @return Creator Instance
     * @see http://www.autodesk.com/techpubs/autocad/acad2000/dxf/line_dxf_06.htm
     */
    public function addLine($x, $y, $z, $x2, $y2, $z2)
    {
        $x += $this->offset[0];
        $y += $this->offset[1];
        $z += $this->offset[2];
        $x2 += $this->offset[0];
        $y2 += $this->offset[1];
        $z2 += $this->offset[2];
        $this->shapes[] = "LINE\n  8\n{$this->layerName}\n 10\n{$x}\n 20\n{$y}\n 30\n{$z}\n 11\n{$x2}\n 21\n{$y2}\n 31\n{$z2}\n  0\n";
        return $this;
    }


    /**
     * Add text to current layer
     * @param float $x
     * @param float $y
     * @param float $z
     * @param string $text
     * @param float $textHeight Text height
     * @param integer $position Position of text from point: 1 = top-left; 2 = top-center; 3 = top-right; 4 = center-left; 5 = center; 6 = center-right; 7 = bottom-left; 8 = bottom-center; 9 = bottom-right
     * @param float $angle Angle of text in degrees (rotation)
     * @return Creator Instance
     * @see http://www.autodesk.com/techpubs/autocad/acad2000/dxf/text_dxf_06.htm
     */
    public function addText($x, $y, $z, $text, $textHeight, $position = 7, $angle = 0)
    {
        $x += $this->offset[0];
        $y += $this->offset[1];
        $z += $this->offset[2];
        $angle = deg2rad($angle);
        $this->shapes[] = "TEXT\n  8\n{$this->layerName}\n 10\n{$x}\n 20\n{$y}\n 30\n{$z}\n 40\n{$textHeight}\n 71\n{$position}\n  1\n{$text}\n 50\n{$angle}\n  0\n";
        return $this;
    }


    /**
     * Add circle to current layer
     * @param float $x
     * @param float $y
     * @param float $z
     * @param float $radius
     * @return Creator Instance
     * @see http://www.autodesk.com/techpubs/autocad/acad2000/dxf/circle_dxf_06.htm
     */
    public function addCircle($x, $y, $z, $radius)
    {
        $x += $this->offset[0];
        $y += $this->offset[1];
        $z += $this->offset[2];
        $this->shapes[] = "CIRCLE\n  8\n{$this->layerName}\n 10\n{$x}\n 20\n{$y}\n 30\n{$z}\n 40\n{$radius}\n  0\n";
        return $this;
    }

    public function addTriangle($x, $y, $size)
    {
        $coordinates = [];
        $half = $size / 2;
        $coordinates[] = $x; $coordinates[] = $y + $half;
        $coordinates[] = $x + $half; $coordinates[] = $y - $half;
        $coordinates[] = $x - $half; $coordinates[] = $y - $half;
        $coordinates[] = $x; $coordinates[] = $y + $half;
        $this->addPolyline3d($coordinates);
        return $this;
    }

    public function addSquare($x, $y, $size)
    {
        $coordinates = [];
        $half = $size / 2;
        $coordinates[] = $x - $half; $coordinates[] = $y + $half;
        $coordinates[] = $x + $half; $coordinates[] = $y + $half;
        $coordinates[] = $x + $half; $coordinates[] = $y - $half;
        $coordinates[] = $x - $half; $coordinates[] = $y - $half;
        $coordinates[] = $x - $half; $coordinates[] = $y + $half;
        $this->addPolyline3d($coordinates);
        return $this;
    }


    /**
     * Add Arc to current layer.
     * Don't forget: it's drawing by counterclock-wise.
     * @param float $x
     * @param float $y
     * @param float $z
     * @param float $radius
     * @param float $startAngle
     * @param float $endAngle
     * @return $this
     * @see http://www.autodesk.com/techpubs/autocad/acad2000/dxf/arc_dxf_06.htm
     */
    public function addArc($x, $y, $z, $radius, $startAngle = 0.1, $endAngle = 90.0)
    {
        $x += $this->offset[0];
        $y += $this->offset[1];
        $z += $this->offset[2];
        $this->shapes[] = "ARC\n  8\n{$this->layerName}\n 10\n{$x}\n 20\n{$y}\n 30\n{$z}\n 40\n{$radius}\n 50\n{$startAngle}\n 51\n{$endAngle}\n  0\n";
        return $this;
    }


    /**
     * Add Ellipse to current layer.
     *
     * @return $this
     * @see http://www.autodesk.com/techpubs/autocad/acad2000/dxf/ellipse_dxf_06.htm
     */
    // TODO todo...
//    public function addEllipse(/* ... */)
//    {
//
//        return $this;
//    }


    /**
     * Add 2D polyline to current layer.
     * @param array[float] $points Points array: [x, y, x2, y2, x3, y3, ...]
     * @return $this
     * @see http://www.autodesk.com/techpubs/autocad/acad2000/dxf/lwpolyline_dxf_06.htm
     */
    public function addPolyline2d($points)
    {
        $count = count($points);
        if ($count > 2 && ($count % 2) == 0) {
            $dots = " 90\n" . ($count / 2 + 1) . "\n";
            $polyline = "LWPOLYLINE\n  8\n{$this->layerName}\n{$dots}";
            for ($i = 0; $i < $count; $i += 2) {
                $points[$i] += $this->offset[0];
                $points[$i + 1] += $this->offset[1];
                $polyline .= " 10\n{$points[$i]}\n 20\n{$points[$i+1]}\n 30\n  0\n";
            }
            $this->shapes[] = $polyline . "  0\n";
        }
        return $this;
    }

    public function addPolyline3d($points)
    {
        $count = count($points);
        if ($count > 2 && ($count % 2) == 0) {
            $polyline = "POLYLINE\n  8\n{$this->layerName}\n 66\n1\n 10\n0\n 20\n0\n 30\n0\n  0\n";
            for ($i = 0; $i < $count; $i += 2) {
                $points[$i] += $this->offset[0];
                $points[$i + 1] += $this->offset[1];
                $polyline .= "VERTEX\n  8\n{$this->layerName}\n 10\n{$points[$i]}\n 20\n{$points[$i+1]}\n 30\n0\n  0\n";
            }
            $this->shapes[] = $polyline . "SEQEND\n  8\n{$this->layerName}\n  0\n";
        }
        return $this;
    }

    public function __toString()
    {
        return $this->getString();
    }
}
