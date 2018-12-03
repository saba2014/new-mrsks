<?php

declare(strict_types = 1);

namespace NavikeyCore\Library;

use ArrayObject;

class FfprobeExt extends Ffprobe {

    public function __construct($filename) {
        parent::__construct($filename);
    }

    public function getVideoStream() {
        foreach ($this->__metadata->streams as $stream) {
            if ($stream->codec_type == 'video') {
                return $stream;
            }
        }
    }

    public function getVideoInfo() {
        $stream = $this->getVideoStream();
        $info = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
        $info->duration = (float) $stream->duration;
        $info->frame_height = (int) $stream->height;
        $info->frame_width = (int) $stream->width;
        eval("\$frame_rate = {$stream->r_frame_rate};");
        $info->frame_rate = (float) $frame_rate;

        return $info;
    }

}
