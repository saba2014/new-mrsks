<?php

declare(strict_types=1);

namespace NavikeyCore\Library;

use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;

class SvgImage
{

    private $color, $unick, $path_images, $icon_path;

    public function __construct(string $dbname, string $icon_path)
    {
        $manager = new Manager();
        $this->icon_path = $icon_path;
        $this->path_images = new Collection($manager, $dbname, "image");
        unset($manager);
    }

    public function getImage($type, $unick, $color)
    {
        $this->color = $color;
        $this->unick = $unick;
        $image = '';
        switch ($type) {
            case 'circle':
                $image = $this->getCircle();
                break;

            case 'triangle':
                $image = $this->getTriangle();
                break;

            case 'square':
                $image = $this->getSquare();
                break;

            case 'lines':
                $image = $this->getLine();
                break;

            case 'opory':
                $image = $this->getOpory();
                break;

            case 'rhombus':
                $image = $this->getRhombus();
                break;

            default:
                $image = $this->getBdImage($type);
                break;
        }
        return $image;
    }

    public function svgToPng($svg, $dpi, $icon_size)
    {
        $im = new \Imagick();
        $im->setOption('density', $dpi);
        $im->setBackgroundColor(new \ImagickPixel('transparent'));
        $im->readImageBlob($svg);
        //$im->adaptiveResizeImage($icon_size,$icon_size);
        $im->setImageFormat("png32");
        $output = $im->getimageblob();
        //file_put_contents("backup/test.png", base64_decode("iVBORw0KGgoAAAANSUhEUgAAABwAAAAcCAYAAAByDd+UAAAABGdBTUEAALGPC\/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAABmJLR0QAAAAAAAD5Q7t\/AAAACXBIWXMAAABgAAAAYADwa0LPAAAAB3RJTUUH4QYVAx4kE09V3QAAANdJREFUSMftkzEOwjAMRZ9ZOiAoG7CCxEVgIvfoyF1go\/doNw4CJwB1oWJhihkaJIRAJKhM5EkZYiv58rcNkUjk75DQB2XJSJWZu+6N4fQzwbJkoUoGpC5UA1tj2Pn+0QkRs5Y1kAAVUImQqLIuCuatVuhs3ACJCMen9EiVqwir5fKzvV4Vup6lwOVF7gKkD31tx9K3FklzfPEV3AO1CL0XFfZVqUU4tCboRn9rLVOannXdGQMTIPfpHwSuhZvGDBi40BnIQ9bim8Uf3gdEhINvZZFI5I+5AYHSSR4AfLQMAAAAJXRFWHRkYXRlOmNyZWF0ZQAyMDE3LTA2LTIxVDEwOjMwOjM2KzA3OjAwjE3m3QAAACV0RVh0ZGF0ZTptb2RpZnkAMjAxNy0wNi0yMVQxMDozMDozNiswNzowMP0QXmEAAAAxdEVYdHN2ZzpiYXNlLXVyaQBmaWxlOi8vL3RtcC9tYWdpY2stMTAwMTNPamFlNnpTcXNBRVR\/eg0bAAAAAElFTkSuQmCC"));
        $im->clear();
        $im->destroy();
        return $output;
    }

    private function getLine()
    {
        $svg = <<<EOM
<?xml version="1.0" encoding="utf-8"?>
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
x="0px" y="0px" viewBox="-282 405.9 30 30" style="enable-background:new -282 405.9 30 30;" 
xml:space="preserve"><style type="text/css">
.st_{$this->unick}_0{fill:none;stroke:{$this->color};stroke-width:3;stroke-linecap:round;
stroke-linejoin:round;}
.st_{$this->unick}_1{fill:{$this->color};fill-opacity:0.6;stroke:{$this->color};
stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;}</style>
<path class="st_{$this->unick}_1" 
d="M-271,420.9c0,2.2,1.8,4,4,4s4-1.8,4-4c0-2.2-1.8-4-4-4S-271,418.7-271,420.9"/>
</svg>
EOM;
        return $svg;
    }

    private function getOpory()
    {
        $svg = <<<EOM
<?xml version="1.0" encoding="utf-8"?>
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
x="0px" y="0px" viewBox="-282 405.9 30 30" style="enable-background:new -282 405.9 30 30;" 
xml:space="preserve"><style type="text/css">
.st_{$this->unick}_0{fill:none;stroke:{$this->color};stroke-width:3;stroke-linecap:round;
stroke-linejoin:round;}
.st_{$this->unick}_1{fill:{$this->color};fill-opacity:0.6;stroke:{$this->color};
stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;}</style>
<path class="st_{$this->unick}_1" 
d="M-271,420.9c0,2.2,1.8,4,4,4s4-1.8,4-4c0-2.2-1.8-4-4-4S-271,418.7-271,420.9"/>
</svg>
EOM;
        return $svg;
    }

    private function getCircle()
    {
        $svg = <<<EOM
<?xml version="1.0" encoding="utf-8"?>
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" 
y="0px" viewBox="-282 408.8 27 27.1" style="enable-background:new -282 408.8 27 27.1;" 
xml:space="preserve"><style type="text/css">
.ps_{$this->unick}_0{fill:url(#PS_{$this->unick});fill-opacity:0.6;stroke:{$this->color};stroke-width:2;
stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;</style>
<linearGradient id="PS_{$this->unick}" gradientUnits="userSpaceOnUse" x1="-546.2546" y1="815.8112" 
x2="-545.2546" y2="814.8112" gradientTransform="matrix(15.7569 0 0 -15.5175 8331.0264 13073.7285)">
<stop  offset="0" style="stop-color:#FFFFFF"/>
<stop  offset="0.6" style="stop-color:{$this->color}"/>
</linearGradient>
<path shape-rendering="geometricPrecision" class="ps_{$this->unick}_0" 
d="M-263.3,428l-5.1,1.9l-5.1-1.9l-2.7-4.7l1-5.4l4.2-3.5h5.5l4.2,3.5l1,5.4L-263.3,428z"/>
</svg>
EOM;
        return $svg;
    }

    private function getTriangle()
    {
        $svg = <<<EOM
<?xml version="1.0" encoding="utf-8"?>
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
x="0px" y="0px" viewBox="-282 405.9 30 30" style="enable-background:new -282 405.9 30 30;" 
xml:space="preserve"><style type="text/css">
.tp_{$this->unick}_0{fill:url(#tp_{$this->unick});fill-opacity:0.6;stroke:{$this->color};stroke-width:2;
stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;</style>
<linearGradient id="tp_{$this->unick}" gradientUnits="userSpaceOnUse" x1="-543.8571" y1="812.78" 
x2="-542.8571" y2="811.78" gradientTransform="matrix(14 0 0 -14 7340 11792.8105)">
<stop  offset="0" style="stop-color:#FFFFFF"/>
<stop  offset="0.6" style="stop-color:{$this->color}"/>
</linearGradient>
<path shape-rendering="geometricPrecision" class="tp_{$this->unick}_0" d="M-274,427.9l7-14l7,14H-274z"/>
</svg>
EOM;
        return $svg;
    }

    private function getSquare()
    {
        $svg = <<<EOM
<?xml version="1.0" encoding="utf-8"?>
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
x="0px" y="0px" viewBox="-282 405.9 30 30" style="enable-background:new -282 405.9 30 30;" 
xml:space="preserve"><style type="text/css">
.rp_{$this->unick}_0{fill:url(#rp_{$this->unick});fill-opacity:0.6;stroke:{$this->color};stroke-width:2;
stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;</style>
<linearGradient id="rp_{$this->unick}" gradientUnits="userSpaceOnUse" x1="-543.8571" y1="812.78" 
x2="-542.8571" y2="811.78" gradientTransform="matrix(14 0 0 -14 7340 11792.8105)">
<stop  offset="0" style="stop-color:#FFFFFF"/>
<stop  offset="0.6" style="stop-color:{$this->color}"/>
</linearGradient>
<path shape-rendering="geometricPrecision" class="rp_{$this->unick}_0" d="M-274,427.9v-14h14v14H-274z"/>
</svg>
EOM;
        return $svg;
    }

    private function getRhombus()
    {
        $svg = <<<EOM
<?xml version="1.0" encoding="utf-8"?>
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" 
y="0px" viewBox="-282 408.9 26.8 26.9" style="enable-background:new -282 408.9 26.8 26.9;" 
xml:space="preserve"><style type="text/css">
.ztp_{$this->unick}_0{fill:url(#ZTP_{$this->unick});fill-opacity:0.6;stroke:{$this->color};stroke-width:2;
stroke-linecap:round;stroke-linejoin:round;stroke-opacity:0.9;</style>
<linearGradient id="ZTP_{$this->unick}" gradientUnits="userSpaceOnUse" x1="-546.3489" y1="816.3859" 
x2="-545.3489" y2="815.3859" gradientTransform="matrix(16 0 0 -16 8465 13476.3701)">
<stop  offset="0" style="stop-color:#FFFFFF"/>
<stop  offset="0.6" style="stop-color:{$this->color}"/>
</linearGradient>
<path shape-rendering="geometricPrecision" class="ztp_{$this->unick}_0" 
d="M-260.6,422.2l-8,8l-8-8l8-8L-260.6,422.2z"/>
</svg>
EOM;
        return $svg;
    }

    private function getBdImage(string $type)
    {
        $path = $this->path_images->findOne(["name" => $type]);
        return file_get_contents($this->icon_path . $path["path"]);
    }

}
