<?php

declare(strict_types=1);

use Phalcon\Mvc\Controller;
use MongoDB\Driver\Manager;
use Phalcon\Db\Adapter\MongoDB\Collection;
use \ReCaptcha\ReCaptcha;
use NavikeyCore\Library\TokenHandler as Token;

function exception_error_handler($severity, $message, $file, $line)
{
    throw new ErrorException($message, 0, $severity, $file, $line);
}

class LoginController extends Controller
{

    protected $users;

    public function initialize()
    {
        $this->tag->setTitle('Войти');
        $manager = new Manager();
        $this->users = new Collection($manager, $this->config->database->dbname, "users");
    }

    private function _startSession($user)
    {
        $obj = [
            "name" => $user->name,
            "role" => $user->role,
            "id" => base64_encode($user->name)
        ];
        $token = new Token($this->config->token, $this->config->database->dbname);
        $res = $token->createTokenPair($obj);
        $res['role'] = $user->role;
        echo json_encode($res);
    }

    /**
     * Finishes the active session redirecting to the index
     *
     * @return unknown
     */
    public function endAction()
    {
        $token = new Token($this->config->token, $this->config->database->dbname);
        $token->userExit($this->request);
        $this->flash->clear();
        return $this->response->redirect("index");
    }

    private function getUser()
    {
        $post = $this->request->getPost();
        $user = $this->users->findOne(["name" => $post["username"]], []);
        if (isset($user) && password_verify($post["password"], $user["password"])) {
            return $user;
        } else {
            return;
        }
    }


    private function getUserServer()
    {
        $post = $this->request->getPost();
        $login_server = $this->config->mask->login_server;
        $ldap = ldap_connect($login_server, 3268);
        if ($ldap === false) {
            $this->logger->error("Невозможно соединиться с $login_server");
            return;
        }
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

        if (!array_key_exists("username", $post) || !array_key_exists("password", $post) || $post["username"] == "" || $post["password"] == "") {
            echo "Неверный логин/пароль, т.к. одно из полей пустое!";
            exit;
        }
        $username = $post["username"];
        $password = $post["password"];
        $ldaprdn = $username;
        if (strstr($username, '\\') === false) {
            $user = $username;
            $domain = 'mrsks';
            $ldaprdn = $domain . '\\' . $username;
        } else {
            list($domain, $user) = explode('\\', $username);
        }
        set_error_handler("exception_error_handler");
        try {
            $bind = ldap_bind($ldap, $ldaprdn, $password);
        } catch (ErrorException $ex) {
            $bind = false;
        }
        restore_error_handler();
        if ($bind) {
            $role = $this->getRoleLdp($ldap, $user);
        } else {
            $role = "Guests";
        }

        ldap_close($ldap);
        return (object)["name" => $username, "role" => $role];
    }

    private function getRoleLdp($ldap, $user)
    {
        $role = "Users";
        $filter = "(sAMAccountName=$user)";
        $result = ldap_search($ldap, 'dc=MRSKS,dc=LOCAL', $filter);
        if ($result === false) {
            return $role;
        }
        ldap_sort($ldap, $result, 'sn');
        $info = ldap_get_entries($ldap, $result);
        if (isset($info)) {
        }
        if (!array_key_exists('memberof', $info[0])) {
            return $role;
        }
        $logingroups = $info[0]['memberof'];
        if (!isset($logingroups)) {
            return $role;
        }
        if (in_array($this->config->application->logingroup, $logingroups)) {
            $role = "Admin";
        }
        $manager = new Manager();
        $model = new Collection($manager, $this->config->database->dbname, "admins");
        $admin_ldap = $model->findOne(["name" => $logingroups]);
        if (isset($admin_ldap)) {
            $role = $admin_ldap["role"];
        }
        $admin_krsk = $model->findOne(["name" => $user]);
        if (isset($admin_krsk)) {
            $role = $admin_krsk["role"];
        }
        return $role;
    }

    public function reloginAction()
    {
        $this->view->disable();
        $user = $this->getUserServer();
        if (!isset($user) || (!strcmp($user->role, "Guests"))) {
            if (strcmp($user->role, "Users") != 0)
                $user = $this->getUser();
        }
        if ((isset($user) && $user !== false)) {

        } else {
            echo "Неверный логин/пароль. Повторите ввод данных.";
        }
    }


    public function checkUserAction()
    {
        $this->view->disable();
        $res = [];
        $res['role'] = "Guests";
        $token = new Token($this->config->token, $this->config->database->dbname);
        $res['role'] = $token->getRole($this->request);
        echo json_encode($res);
    }

    public function captchaAction()
    {
    }

    public function checkCaptchaAction()
    {
        $this->view->disable();
        $captcha = new ReCaptcha($this->config->application->captchaSecretKey);
        $resp = $captcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
        if ($resp->isSuccess()) {

        } else {
            $this->response->redirect("login/captcha");
        }
    }


    public function getLoginViewAction()
    {
        $this->view->setRenderLevel(4);
        $get = $this->request->getQuery();
        $url = "";
        $this->flash->clear();
        foreach ($get as $key => $item) {
            if (strcmp($key, "_url")) {
                $url = $url . "$key=$item&";
            }
        }
        $this->view->redirect = $url;
    }

}
