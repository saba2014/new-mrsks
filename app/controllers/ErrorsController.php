<?php
declare(strict_types = 1);

class ErrorsController extends NavikeyCore\Controllers\ControllerBase {

    public function initialize() {
        $this->tag->setTitle('Ошибка');
        parent::initialize();        
        $lib_js = $this->assets->collection("lib_js");
        $lib_css = $this->assets->collection("lib_css");
        $path = json_decode(file_get_contents("../var/config/path.json"), true);
        $this->cheackHashJs($path["main"]["lib_js"], "", $lib_js);
        $this->cheackHashCss($path["main"]["lib_css"], "", $lib_css);
    }

    public function show400Action() {

    }

    public function show401Action() {
        //if(!strcmp($this->router->getControllerName(), "map")) {
            return $this->response->redirect("index");
        //}
    }

    public function show403Action() {

    }

    public function show404Action() {
        $name = $this->router->getControllerName();
        if (isset($name)) {
            if (!strcmp($name, "map") || !strcmp($name, "Map")) {
                return $this->response->redirect("index");
            }
        }
    }

    public function show500Action() {
        
    }

}
