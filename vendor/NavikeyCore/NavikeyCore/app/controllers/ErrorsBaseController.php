<?php
declare(strict_types = 1);

namespace NavikeyCore\Controllers;

class ErrorsBaseController extends ControllerBase {

    public function initialize() {
        $this->tag->setTitle('Ошибка');
        parent::initialize();
    }

    public function show404Action() {
        
    }

    public function show401Action() {
        
    }

    public function show403Action() {
        
    }

    public function show500Action() {
        
    }

}
