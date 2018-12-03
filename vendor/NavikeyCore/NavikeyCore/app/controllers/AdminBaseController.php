<?php
declare(strict_types=1);

namespace NavikeyCore\Controllers;

use Phalcon\Mvc\View;
use NavikeyCore\Library\TokenHandler as Token;

class AdminBaseController extends ControllerBase
{

    public function initialize()
    {
        $this->tag->setTitle('Администрирование');
        parent::initialize();
        header('Content-Type: text/html; charset=utf-8');
    }

    public function getRole(){
        $tokenHandler = new Token($this->config->token,$this->config->database->dbname);
        $role = $tokenHandler->getRole($this->request);
        return $role;
    }

    public function indexAction()
    {
        $role = $this->getRole();
        /*$role = $this->session->get('auth')["role"];
        session_write_close();*/
        $this->view->secret_admin = 0;
        if (!strcmp($role, "Master_admin")) {
            $this->view->admin = $this->view->secret_admin = 1;
        }
        $lib_js = $this->assets->collection("lib_js");
        $lib_css = $this->assets->collection("lib_css");
        $css = $this->assets->collection("css");
        $js = $this->assets->collection("admin_js");
        $path = json_decode(file_get_contents("../var/config/path.json"), true);
        $this->cheackHashJs($path["main"]["lib_js"], "", $lib_js);
        $this->cheackHashCss($path["main"]["lib_css"], "", $lib_css);
        $this->cheackHashCss($path["main"]["css"], "", $css);
        $this->cheackHashJs($path["main"]["admin_js"], "", $js);
    }

    public function loginfoAction()
    {
        $this->view->setRenderLevel(
            View::LEVEL_NO_RENDER
        );
        $st_log = file_get_contents($this->config->log->import);
        $st = nl2br($st_log);
        print_r($st);
    }

    public function configAction()
    {
        $this->view->setRenderLevel(
            View::LEVEL_NO_RENDER
        );
        $st = file_get_contents($this->config->mask->import_config);
        echo $st;
    }

    public function saveAction()
    {
        $post = $this->request->getPost();
        $get = $this->request->getQuery();
        $data = isset($post['data']) ? $post['data'] : $get['data'];
        file_put_contents($this->config->mask->import_config, $data);
    }

}
