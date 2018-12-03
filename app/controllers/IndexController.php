<?php

declare(strict_types = 1);

use Ds\Vector;
use Ds\Map;
use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;
use NavikeyCore\Library\CheckTplnr;
use NavikeyCore\Library\Territory;
use NavikeyCore\Library\Menu;
use NavikeyCore\Models\Users;

class IndexController extends NavikeyCore\Controllers\IndexBaseController {

    public function initialize(): void {
        parent::initialize();
        $this->view->masks = new CheckTplnr($this->config->mask->maskurl);
        $this->view->masks->loadSortMask($this->config->mask->path_sort_legend);
        if (file_exists($this->config->mask->path_sort_legend)) {
            $this->view->mask_json = file_get_contents($this->config->mask->path_sort_legend);
        } else {
            $this->view->mask_json = "{}";
        }
        if (file_exists($this->config->mask->path_sort_legend_univers)) {
            $this->view->univers_mask_json = file_get_contents($this->config->mask->path_sort_legend_univers);
        } else {
            $this->view->univers_mask_json = "{}";
        }
        $territory = new Territory($this->config->database->dbname);
        $this->view->tree_territory = json_encode($territory->getTerritory());
        $this->view->http_host = filter_input(INPUT_SERVER, 'HTTP_HOST');
        $this->view->admin = 0;
        $this->view->secret_admin = 0;
        $roleCode = 0;
        $role="Guests";
        $this->view->role = $role;
        if (!strcmp($role, "Admin")) {
            $this->view->admin = 1;
            $roleCode = 2;
        }
        if (!strcmp($role, "Master_admin")) {
            $this->view->admin = $this->view->secret_admin = 1;
            $roleCode = 3;
        }
        $this->view->role = $roleCode;
        $this->view->icons = $this->config->application->path_icons;
        $this->view->usecache = $this->config->application->usecache;
    }

    public function indexAction($redirect = ""): void {
        $this->view->sapUrl = "test/url";
        $this->addJsCss();
        $this->setView();
        $this->setRegions();
    }

    private function setView(): void {
        $main_menu = new Menu($this->config->database->dbname, $this->config->application->iconsDir, $this->config->mask->path_sort_legend, $this->config->mask->path_sort_legend_univers);
        $users = new Users($this->config->database->dbname);
        $menu = new Vector();
        $menu->push(...$main_menu->getMenu($main_menu->findMenu("shared", "free")));
        if (isset($user_id)) {
            $menu->push(...$main_menu->getMenu($main_menu->findMenu("owner_id", $user_id)));
        }
        unset($main_menu);
        $zoom = 14;
        if (isset($_COOKIE['lat'])&&isset($_COOKIE['lon']))
        {
            $coords = [$_COOKIE['lat'],$_COOKIE['lon']];
        }
        else $coords = [56.006, 92.832];
        if (isset($_COOKIE['zoom']))
            $zoom = $_COOKIE['zoom'];

        $cookie = filter_input(INPUT_COOKIE, "zoom");
        if (isset($cookie))
            if (isset($cookie["lat"])&&isset($cookie["lon"])){
                $coords = [filter_input(INPUT_COOKIE, "lat"), filter_input(INPUT_COOKIE, "lon")];
                $zoom = $cookie;
            }
        $this->view->menu = json_encode($menu);
        $this->view->coord_1 = $coords[0];
        $this->view->coord_2 = $coords[1];
        $this->view->zoom = $zoom;
        $this->view->types = new Map();
        $this->view->types["line"] = $this->getLegends("line");
        $this->view->types["tp"] = $this->getLegends("tp");
        $this->view->types["rp"] = $this->getLegends("rp");
        $this->view->types["ps"] = $this->getLegends("ps");
        $this->view->types["applicant_1"] = $this->ZTP("#7030A0", "applicant_1");
        $this->view->types["applicant_2"] = $this->ZTP("#E46C0A", "applicant_2");
        $this->view->types["applicant_3"] = $this->ZTP("#BB0000", "applicant_3");
        $path = $this->config->application->iconsDir;
        $this->view->types["RISE"] = file_get_contents($path . "rise.svg");
        $this->view->types["Emergency"] = file_get_contents($path . "emergency.svg");
        $this->view->types["Center"] = file_get_contents($path . "resCenter.svg");
        $this->view->types["Search"] = file_get_contents("css/images/icon-search-24px.svg");
    }

    private function getView(): Map {
        return new Map([
            '@mrsks.ru' => [56.006, 92.832],
            '@kr.mrsks.ru' => [56.006, 92.832],
            '@mrsks.local' => [56.006, 92.832],
            '@ba.mrsks.ru' => [53.351239, 83.758484],
            '@ul.mrsks.ru' => [51.823953, 107.607398],
            '@ke.mrsks.ru' => [55.344331, 86.060684],
            '@om.mrsks.ru' => [54.993164, 73.361933],
            '@ch.mrsks.ru' => [52.047643, 113.460533],
            '@ab.mrsks.ru' => [53.716701, 91.437890]
        ]);
    }

    private function setRegions(): void {
        $manager = new Manager();
        $region = new Collection($manager, $this->config->database->dbname, "regions");
        $cursor = $region->find();
        $this->sel_cont = '';
        foreach ($cursor as $document) {
            $this->view->sel_cont .= "<option value='{$document->kod}'>{$document->name}</option>";
        }
    }

    private function getLegends(string $type): string {
        $st = "";
        foreach ($this->view->masks->$type as $mask) {
            $st = $st . "<div class='row'><div class='col-2'>" . $this->$type($mask) .
                    "</div><div class='col-6 lep'>" . $mask['voltage'] . " кв</div></div>";
        }
        return $st;
    }

    private function line(array $mask): string {
        return '<svg height="30" width="30">                      
                        <path class="leaflet-interactive" stroke="' . $mask['color'] . '" stroke-opacity="1" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none" d="M1 15L30 15"></path>    
                        <path class="leaflet-interactive" stroke="' . $mask['color'] . '" stroke-opacity="0.9" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" fill="' . $mask['color'] . '" fill-opacity="0.6" fill-rule="evenodd" d="M11,15a4,4 0 1,0 8,0 a4,4 0 1,0 -8,0"></path>
                        </svg>';
    }

    private function ps(array $mask): string {
        return '<svg height="30" width="30">                      
                        <path class="leaflet-interactive" stroke="' . $mask['color'] . '" stroke-opacity="0.9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="url(#TPS_' . $mask['voltage'] . ')" fill-opacity="0.6" fill-rule="evenodd" shape-rendering="geometricPrecision" d="M21.142301 25.128356 L16.000000 27.000000 L10.857699 25.128356 L8.121538 20.389185 L9.071797 15.000000 L13.263839 11.482459 L18.736161 11.482459 L22.928203 15.000000 L23.878462 20.389185Z"></path>
                        <defs>
                        <linearGradient x1="0%" x2="100%" y1="0%" y2="100%" id="TPS_' . $mask['voltage'] . '">
                        <stop offset="0%" style="stop-color:rgb(255, 255, 255);stop-opacity:1;"></stop>
                        <stop offset="60%" style="stop-color:' . $mask['color'] . ';stop-opacity:1;"></stop>
                        </linearGradient>
                        </defs>
                        </svg>';
    }

    private function rp(array $mask): string {
        return '<svg height="30" width="30">'
                . '<path class="leaflet-interactive" stroke="' . $mask['color'] .
                '" stroke-opacity="0.9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="url(#RP_' . $mask['voltage'] . ')" fill-opacity="0.6" fill-rule="evenodd" shape-rendering="geometricPrecision" d="M 8 22 L 8 8 L 22 8 L 22 22 z"></path>
                        <defs>
                        <linearGradient x1="0%" x2="100%" y1="0%" y2="100%" id="RP_' . $mask['voltage'] . '">
                        <stop offset="0%" style="stop-color:rgb(255, 255, 255);stop-opacity:1;"></stop>
                        <stop offset="60%" style="stop-color:' . $mask['color'] . ';stop-opacity:1;"></stop>
                        </linearGradient>
                        </defs>
                        </svg>';
    }

    private function tp(array $mask): string {
        return '<svg height="30" width="30">                      
                        <path class="leaflet-interactive" stroke="' . $mask['color'] . '" stroke-opacity="0.9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="url(#TP_' . $mask['voltage'] . ')" fill-opacity="0.6" fill-rule="evenodd" shape-rendering="geometricPrecision" d="M 8 22 L 15 8 L 22 22 z"></path>
                        <defs>
                        <linearGradient x1="0%" x2="100%" y1="0%" y2="100%" id="TP_' . $mask['voltage'] . '">
                        <stop offset="0%" style="stop-color:rgb(255, 255, 255);stop-opacity:1;"></stop>
                        <stop offset="60%" style="stop-color:' . $mask['color'] . ';stop-opacity:1;"></stop>
                        </linearGradient>
                        </defs>
                        </svg>';
    }

    private function ZTP(string $color, string $unic): string {
        return '<svg height="30" width="30">                      
                        <path class="leaflet-interactive" stroke="' . $color . '" stroke-opacity="0.9" 
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                            fill="url(#' . $unic . ')" fill-opacity="0.6" fill-rule="evenodd" 
                            shape-rendering="geometricPrecision" d="M21 16 L13 24 L5 16 L13 8Z"></path>
                        <defs>
                        <linearGradient x1="0%" x2="100%" y1="0%" y2="100%" id="' . $unic . '">
                        <stop offset="0%" style="stop-color:rgb(255, 255, 255);stop-opacity:1;"></stop>
                        <stop offset="60%" style="stop-color:' . $color . ';stop-opacity:1;"></stop>
                        </linearGradient>
                        </defs>
                        </svg>';
    }

}
