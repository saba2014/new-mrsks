<?php

declare(strict_types = 1);

namespace NavikeyCore\Controllers;

use Phalcon\Mvc\Controller;

class IndexBaseController extends Controller {

    public function initialize() {
        header('Content-Type: text/html; charset=utf-8');
    }

    public function indexAction() {

    }

    protected function addJsCss() {
        $path = json_decode(file_get_contents("../var/config/path.json"), true);

        $lib_js = $this->assets->collection("lib_js");
        $this->cheackHashJs($path["main"]["lib_js"], "", $lib_js,'js/min/lib_js');

        $js = $this->assets->collection("js");
        $this->cheackHashJs($path["main"]["js"], "", $js,'js/min/js');

        $lib_css = $this->assets->collection("lib_css");
        $this->cheackHashCss($path["main"]["lib_css"], "", $lib_css,'css');

        $css = $this->assets->collection("css");
        $this->cheackHashCss($path["main"]["css"], "", $css,'css/min');
    }

    protected function cheackHashJs($path, $dir, $collection,$insideDir) {
        $time = 0;
        foreach ($path["min"] as $min) {
            if (file_exists($dir . $min)) {
                $time += filectime($dir . $min);
            }
        }
        foreach ($path["src"] as $min) {
            if (file_exists($dir . $min)) {
                $time += filectime($dir . $min);
            }
        }
        $md5 = $insideDir.'/'. md5((string) $time) . ".minification.js";
        if (file_exists($md5) && !$this->config->application->debug) {
            $collection->addJs($md5, true, false);
        } else {
            $scanned_directory = array_diff(scandir($insideDir), array('..', '.'));
            foreach ($scanned_directory as $scann_dir) {
                if (strpos($scann_dir, "minification")) {
                    unlink($insideDir.'/'.$scann_dir);
                }
            }
            foreach ($path["min"] as $min) {
                $collection->addJs($dir . $min, true);
            }
            foreach ($path["src"] as $min) {
                $collection->addJs($dir . $min, true, false);
            }
            if (!$this->config->application->debug) {
                $collection->join(true)
                        ->setTargetPath($md5)
                        ->setTargetUri($md5);
                $collection->addFilter(new \Phalcon\Assets\Filters\Jsmin());
            }
        }
    }

    protected function cheackHashCss($path, $dir, $collection, $insideDir) {
        $time = 0;
        foreach ($path["min"] as $min) {
            if (file_exists($dir . $min)) {
                $time += filectime($dir . $min);
            }
        }
        foreach ($path["src"] as $min) {
            if (file_exists($dir . $min)) {
                $time += filectime($dir . $min);
            }
        }
        $md5 = $insideDir.'/'.md5((string) $time) . ".minification.css";
        if (file_exists($md5) && !$this->config->application->debug) {
             $collection->addCss($md5, true, false);
        } else {
            $scanned_directory = array_diff(scandir($insideDir), array('..', '.'));
            foreach ($scanned_directory as $scann_dir) {
                if (strpos($scann_dir, "minification")) {
                    unlink($insideDir.'/'.$scann_dir);
                }
            }
            foreach ($path["min"] as $min) {
                $collection->addCss($dir . $min, true);
            }
            foreach ($path["src"] as $min) {
                $collection->addCss($dir . $min, true);
            }
            if (!$this->config->application->debug) {
                $collection->join(true)
                        ->setTargetPath($md5)
                        ->setTargetUri($md5);
                $collection->addFilter(new \Phalcon\Assets\Filters\Cssmin());
            }
        }
    }

}
