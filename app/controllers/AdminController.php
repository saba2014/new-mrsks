<?php
declare(strict_types = 1);

class AdminController extends NavikeyCore\Controllers\AdminBaseController {

    public function initialize(): void{
        parent::initialize();
        $roleCode = 0;
        $role=parent::getRole();
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
    }

}
