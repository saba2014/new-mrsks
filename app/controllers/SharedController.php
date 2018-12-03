<?php
declare(strict_types = 1);

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\View;

class SharedController extends Controller {

    public function initialize() {
        $this->view->setRenderLevel(
                View::LEVEL_NO_RENDER
        );
        header('Content-Type: application/json; charset=UTF-8');
    }

}
