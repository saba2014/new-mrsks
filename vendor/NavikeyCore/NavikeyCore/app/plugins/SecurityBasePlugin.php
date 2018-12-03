<?php

declare(strict_types = 1);

namespace NavikeyCore\Plugins;

use Phalcon\Acl;
use Phalcon\Acl\Role;
use Phalcon\Acl\Resource;
use Phalcon\Events\Event;
use Phalcon\Mvc\User\Plugin;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Acl\Adapter\Memory as AclList;

/**
 * SecurityPlugin
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class SecurityBasePlugin extends Plugin {

    /**
     * Returns an existing or new access control list
     *
     * @returns AclList
     */
    public function getAcl() {
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
                'index' => ['index'],
                'login' => ['index', 'login', 'end'],
                'errors' => ['show401', 'show403', 'show404', 'show500'],
                'api' => ['index','registration','confirmRegistration','refreshTokens', 'userinfo', 'getobjs', 'auth', 'getmenu', 'setmenu', 'updatemenu',
                    'svgtopng', 'srtm', 'delobj', 'upload', 'location', 'update', 'deletehref', 'message',
                    'uploadtmp', 'searchkadastr', 'savekml', 'refreshmap', 'saveGeoJson', 'icons', 'del', 'create',
                    'checkvk', 'reguser', 'userout', 'loguser', 'postmessage','getNames','getTest','getRoleList'],
                'shared' => ['index']
            ];
            $userResources = [
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
            $this->allowResurse(['Guests', 'Users', 'Admin', 'Master_admin'], $publicResources, $acl);
            $this->allowResurse(['Users', 'Admin', 'Master_admin'], $userResources, $acl);
            $this->allowResurse(['Admin', 'Master_admin'], $adminResources, $acl);
            $this->allowResurse(['Master_admin'], $master_adminResources, $acl);
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
    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher) {
        $auth = true;
        $role="Guests";
        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();
        $acl = $this->getAcl();
        if (!$acl->isResource($controller)) {
            $dispatcher->forward([
                'controller' => 'errors',
                'action' => 'show404'
            ]);
            return false;
        }
        $allowed = $acl->isAllowed($role, $controller, $action);
        //var_dump($role, $controller, $action, $allowed); exit;
        if (!$allowed) {
            if (!$auth) {
                $dispatcher->forward(array(
                    'controller' => 'errors',
                    'action' => 'show401'
                ));
                return false;
            }
            $dispatcher->forward(array(
                'controller' => 'errors',
                'action' => 'show403'
            ));
            return false;
        }
    }

    protected function allowResurse($allows, $resources, $acl) {
        foreach ($allows as $allow) {
            foreach ($resources as $resource => $actions) {
                foreach ($actions as $action) {
                    $acl->allow($allow, $resource, $action);
                }
            }
        }
    }

}
