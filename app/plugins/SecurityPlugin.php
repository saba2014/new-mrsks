<?php

use Phalcon\Acl;
use Phalcon\Acl\Role;
use Phalcon\Acl\Resource;
use Phalcon\Events\Event;
use Phalcon\Mvc\User\Plugin;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Acl\Adapter\Memory as AclList;
use NavikeyCore\Library\TokenHandler as Token;

/**
 * SecurityPlugin
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class SecurityPlugin extends Plugin
{

    /**
     * Returns an existing or new access control list
     *
     * @returns AclList
     */
    public function getAcl()
    {
        if (!isset($this->persistent->acl)) {
            $acl = new AclList();
            $acl->setDefaultAction(Acl::DENY);
            // Register roles
            $roles = [
                'master_admin' => new Role(
                    'Master_admin', 'All privileges, granted after sign in.'
                ),
                'admin' => new Role(
                    'Admin', 'Admin privileges, granted after sign in.'
                ),
                'users' => new Role(
                    'Users', 'Member privileges, granted after sign in.'
                ),
                'guests' => new Role(
                    'Guests', 'Anyone browsing the site who is not signed in is considered to be a "Guest".'
                )
            ];
            foreach ($roles as $role) {
                $acl->addRole($role);
            }
            $publicResources = [
                'index' => ['index', 'login'],
                'login' => ['index', 'login', 'end', 'relogin', 'checkUser', 'captcha', 'checkCaptcha', 'getLoginView'],
                'errors' => ['show401', 'show403', 'show404', 'show500'],
                'api' => ['index','registration', 'refreshTokens', 'getobjs', 'getNameCount', 'getNameObjs', 'getTplnrCount', 'getTplnrObjs', 'auth', 'getmenu',
                    'setmenu', 'updatemenu', 'svgtopng', 'srtm', 'deleteObject', 'upload', 'location', 'update', 'deletehref', 'message', 'uploadtmp',
                    'searchkadastr', 'savekml', 'saveGeoJson', 'icons', 'del', 'create', 'postmessage', 'postmessages', 'getSap', 'getTest', 'getNames',
                    'insertPath', 'getPathItems', 'getAdmin', 'anotherTest','getRoleList','confirmRegistration','getRes','getFiliationZoom','createMobileDivision',
                    'deleteMobileDivision'],
                'map' => ['index']
            ];
            $userResources = [
                'index' => ['index', 'index'],
                'shared' => ['index', 'model_ovb']
            ];
            $adminResources = [
                'admin' => ['index', 'loginfo', 'save', 'config']
            ];
            $master_adminResources = [
                'admin' => ['index', 'master_admin']
            ];
            $arr_resources = [$publicResources, $userResources, $adminResources, $master_adminResources];
            foreach ($arr_resources as $resources) {
                foreach ($resources as $resource => $actions) {
                    $acl->addResource(new Resource($resource), $actions);
                }
            }
            $this->allow_resurse(['Guests', 'Users', 'Admin', 'Master_admin'], $publicResources, $acl);
            $this->allow_resurse(['Users', 'Admin', 'Master_admin'], $userResources, $acl);
            $this->allow_resurse(['Admin', 'Master_admin'], $adminResources, $acl);
            $this->allow_resurse(['Master_admin'], $master_adminResources, $acl);
            //The acl is stored in session, APC would be useful here too
            $this->persistent->acl = $acl;
        }
        return $this->persistent->acl;
    }

    /**
     * This action is executed before execute any action in the application
     *
     * @param Event $event
     * @param Dispatcher $dispatcher
     * @return bool
     */
    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
    {
        // user was authentificated
        $user = [
            'email' => '',
            'role' => 'Guests',
            'name' => ''
        ];
        $tokenHandler = new Token($this->config->token, $this->config->database->dbname);
        $user['role'] = $tokenHandler->getRole($this->request);
        $role = $user["role"];

        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

       // if (!strcmp("refreshTokens", $action)) {
            $tokenHandler = new Token($this->config->token, $this->config->database->dbname);
            $user['role'] = $tokenHandler->getRole($this->request);
            $role = $user["role"];
      //  }

        $acl = $this->getAcl();
        if (!$acl->isResource($controller)) {
            $dispatcher->forward([
                'controller' => 'errors',
                'action' => 'show404'
            ]);
            return false;
        }

        $allowed = $acl->isAllowed($role, $controller, $action);
        if (!$allowed) {
            $dispatcher->forward(array(
                'controller' => 'errors',
                'action' => 'show401'
            ));
            return false;
        }
    }

    private function allow_resurse($allows, $resources, $acl)
    {
        foreach ($allows as $allow) {
            foreach ($resources as $resource => $actions) {
                foreach ($actions as $action) {
                    $acl->allow($allow, $resource, $action);
                }
            }
        }
    }

}
