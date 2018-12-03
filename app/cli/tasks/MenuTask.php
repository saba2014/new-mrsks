<?php

declare(strict_types = 1);

use Phalcon\Cli\Task;

class MenuTask extends Task {
    public function mainAction() {
        $path = $this->config->mask->path_menu;
        file_put_contents($path);
        $masks = new CheckTplnr($this->config->mask->maskurl);
        $masks->loadSortMask($this->config->mask->path_sort_legend);
        if (file_exists($this->config->mask->path_sort_legend)) {
            $mask_json = json_encode(file_get_contents($this->config->mask->path_sort_legend));
        } else {
            $mask_json = [];
        }
        if (file_exists($this->config->mask->path_sort_legend_univers)) {
            $univers_mask_json = json_encode(file_get_contents($this->config->mask->path_sort_legend_univers));
        } else {
            $univers_mask_json = [];
        }
        $territory = new Territory($this->config->database->dbname);
        $tree_territory = $territory->getTerritory();        
    }
}
