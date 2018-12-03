<?php
declare(strict_types = 1);

namespace NavikeyCore\Controllers;

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller {

    protected function initialize() {
        $this->tag->prependTitle("{$this->config->application->title} | ");
        $this->view->setTemplateAfter('index');
    }

    protected function cheackHashJs($path, $dir, $collection) {
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
        $md5 = "js/min/" . md5((string) $time) . ".minification.js";
        if (file_exists($md5) && !$this->config->application->debug) {
            $collection->addJs($md5, true, false);
        } else {
            $scanned_directory = array_diff(scandir("js/min/"), array('..', '.'));
            foreach ($scanned_directory as $scann_dir) {
                if (strpos($scann_dir, "minification")) {
                    unlink("js/min/$scann_dir");
                }
            }
            foreach ($path["min"] as $min) {
                $collection->addJs($dir . $min, true);
            }
            foreach ($path["src"] as $min) {
                $collection->addJs($dir . $min, true, true);
            }
            if (!$this->config->application->debug) {
                $collection->join(true)
                        ->setTargetPath($md5)
                        ->setTargetUri($md5);
                $collection->addFilter(new \Phalcon\Assets\Filters\Jsmin());
            }
        }
    }

    protected function cheackHashCss($path, $dir, $collection) {
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
        $md5 = "css/min/" . md5((string) $time) . ".minification.css";
        if (file_exists($md5) && !$this->config->application->debug) {
            $collection->addCss($md5, true, false);
        } else {
            $scanned_directory = array_diff(scandir("css/min/"), array('..', '.'));
            foreach ($scanned_directory as $scann_dir) {
                if (strpos($scann_dir, "minification")) {
                    unlink("css/min/$scann_dir");
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
                // bug with icons
                //$collection->addFilter(new \Phalcon\Assets\Filters\Cssmin());
            }
        }
    }

}
