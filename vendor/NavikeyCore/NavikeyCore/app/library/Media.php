<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use FFMpeg;
use Imagick;

class Media {

    public function __construct() {
        
    }

    public function load(string $path_dir): array {
        $list = [];
        $dir = dir($path_dir);
        while (false !== ($entry = $dir->read())) {
            if (!strcmp($entry, ".") || !strcmp($entry, "..") || is_dir($entry)) {
                continue;
            }
            $mem_type = mime_content_type($path_dir . $entry);
            $str_arr = explode("/", $mem_type);
            $type = $str_arr[0];
            if (!strcmp($type, "application")) {
                $type = "audio";
            }
            if (!strcmp($type, "image") || !strcmp($type, "video") || !strcmp($type, "audio") || !strcmp($type, "application")) {
                $list[$entry] = $type;
            }
        }
        $dir->close();
        return $list;
    }

    public function delTree($dir) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public function getHrefs(array $list, string $dir): array {
        $hrefs = [];
        $ffmpeg = \FFMpeg\FFMpeg::create([
            'ffmpeg.binaries'  => '/usr/bin/ffmpeg',

    'ffprobe.binaries' => '/usr/bin/ffprobe',

    'timeout'          => 0]);
        $format = new \FFMpeg\Format\Video\Ogg();
        $image_size = 128;
        foreach ($list as $key => $item) {
            $object = [];
            $object["type"] = $item;
            $object["href"] = $dir . $key;
            switch ($item) {
                case "image": {
                        $image = file_get_contents($dir . $key);
                        $new_image = $this->imageSetSize($image, $image_size);
                        file_put_contents($dir . $key . ".preview.png", $new_image);
                        $object["preview"] = $dir . $key . ".preview.png";
                        break;
                    }
                case "video": {
                        exec("ffmpeg -i {$dir}{$key} {$dir}{$key}.webm");
                        $video = $ffmpeg->open("{$dir}{$key}.webm");
                        $object["href"] = "{$dir}{$key}.webm";
                        //$video->save($format, $object["href"] . ".webm");
                        $ffprobe = new FfprobeExt($dir . $key);
                        $info = $ffprobe->getVideoInfo();
                        $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds((int) ($info->duration / 2)));

                        $frame->save($dir . $key . ".preview_video.png");
                        $object["preview_video"] = $dir . $key . ".preview_video.png";
                        $image = file_get_contents($dir . $key . ".preview_video.png");
                        $new_image = $this->imageSetSize($image, $image_size);
                        file_put_contents($dir . $key . ".preview.png", $new_image);
                        $object["preview"] = $dir . $key . ".preview.png";
                        break;
                    }
            }
            array_push($hrefs, $object);
        }
        unset($ffmpeg);
        return $hrefs;
    }

    public function imageSetSize($image, $image_size) {
        $im = new Imagick();
        //$im->setOption('density', $icon_size);
        $im->readImageBlob($image);
        $im->adaptiveResizeImage($image_size, $image_size);
        $im->setImageFormat("png32");
        $output = $im->getimageblob();
        $im->clear();
        $im->destroy();
        return $output;
    }

}
