<?php
declare(strict_types=1);

use MongoDB\Driver\Manager;
use \Phalcon\Db\Adapter\MongoDB\Collection;

function exception_error_handler($severity, $message, $file, $line)
{
    throw new ErrorException($message, 0, $severity, $file, $line);
}

class UsersMrsk extends NavikeyCore\Models\Users
{
    private $collection, $config;

    public function __construct(string $dbname, $config) {
        $this->config = $config;
        $manager = new Manager();
        $this->collection = new Collection($manager, $dbname, "users");
    }

    public function __destruct() {
        unset($this->collection);
    }

    public function getUser(string $username, string $password) {
        $user = $this->getUserServer($username, $password);
        if(isset($user) && $user->role !== "Guests") {
            return $user;
        }
        $user = $this->collection->findOne(["name" => $username], []);
        if(isset($user) && password_verify($password, $user["password"])) {
            return $user;
        } else {
            return false;
        }
    }

    private function getUserServer(string $username, string $password)
    {
        $login_server = $this->config->mask->login_server;
        $ldap = ldap_connect($login_server, 3268);
        if ($ldap === false) {
            $this->logger->error("Невозможно соединиться с $login_server");
            return;
        }
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

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
        if (isset($info))
            $this->info = $info;
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
}