<?php
/* comment for check*/
use Phalcon\Mvc\View;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Mvc\Model\Metadata\Memory as MetaData;
use Phalcon\Events\Manager as EventsManager;

class Services extends \Base\Services {

    /**
     * We register the events manager
     */
    protected function initDispatcher() {
        $eventsManager = new EventsManager;
        /**
         * Check if the user is allowed to access certain action using the SecurityPlugin
         */
        //$eventsManager->attach('dispatch:beforeDispatch', new SecurityPlugin);
        /**
         * Handle exceptions and not-found exceptions using NotFoundPlugin
         */
        //$eventsManager->attach('dispatch:beforeException', new NotFoundPlugin);
        $dispatcher = new Dispatcher;
        $dispatcher->setEventsManager($eventsManager);
        //$dispatcher->set("voltService", $this->_voltService($view, $dispatcher, $config));
        return $dispatcher;
    }

    /**
     * The URL component is used to generate all kind of urls in the application
     */
    protected function initUrl() {
        $url = new UrlProvider();
        $url->setBaseUri($this->get('config')->application->baseUri);
        return $url;
    }

    protected function initView() {
        $view = new View();
        $config = $this->get('config');
        $view->setViewsDir($config->application->viewsDir);
        $view->setLayoutsDir($config->application->viewsDir);
        $view->registerEngines(
                [
                    ".volt" => "voltService",
                    ".phtml" => "voltService"
                ]
        );
        $this->view = $view;
        return $this->view;
    }

    /**
     * Database connection is created based in the parameters defined in the configuration file
     */
    protected function initDb() {
        $config = $this->getShared('config');
        if (!$config->database->username || !$config->database->password) {
            $dsn = 'mongodb://' . $config->database->host;
        } else {
            $dsn = sprintf(
                    'mongodb://%s:%s@%s', $config->database->username, $config->database->password, $config->database->host
            );
        }

        $mongo = new Client($dsn);

        return $mongo->selectDatabase($config->database->dbname);
    }

    /**
     * If the configuration specify the use of metadata adapter use it or use memory otherwise
     */
    protected function initModelsMetadata() {
        return new MetaData();
    }

    protected function _voltService() {
        $config = $this->getShared('config');
        $volt = new Volt($this->view, $this->dispatcher);
        $volt->setOptions(
                [
                    "compiledPath" => $config->application->voltDir,
                    "compiledExtension" => ".compiled",
                ]
        );
        return $volt;
    }

}
