<?php
use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Url;
use Phalcon\Mvc\Collection\Manager;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Db\Adapter\MongoDB\Client;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Mvc\View\Engine\Php;
use Phalcon\Config\Adapter\Ini as ConfigIni;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Session\Adapter\Files as SessionAdapter;
use Phalcon\Flash\Session as FlashSession;
/**
 * Функция разбиения файла с логом
 */

function check_log($logfile) {
    if (filesize($logfile) > 1000000) {
        $inc = 0;
        while (file_exists($logfile . "." . $inc)) {
            $inc++;
        }
        copy($logfile, $logfile . "." . $inc);
        file_put_contents($logfile, "Rotate log at " . date("U"));
    }
}
//$config = new Ini("../var/config/config.ini");

define('APP_PATH', realpath('..') . '/');
/**
 * Read the configuration
 */
if(!file_exists(APP_PATH . "var/config/config.ini")) {
    if(!file_exists(APP_PATH . "var/config/config.default.ini")) {
        echo "Config file is need";
        exit;
    }
    file_put_contents(APP_PATH . "var/config/config.ini", file_get_contents(APP_PATH . "var/config/config.default.ini"));
}

$config = new ConfigIni(APP_PATH . "var/config/config.ini");
//$coreDir = $config->application->coreDir;
//if(!file_exists($config->application->coreConfigDir . "config.ini")) {
//    if(!file_exists($config->application->coreConfigDir . "config.default.ini")) {
//        echo "Config file is need";
//        exit;
//    }
//    file_put_contents($config->application->coreConfigDir . "config.ini", 
//            file_get_contents($config->application->coreConfigDir . "config.default.ini"));
//}
//if (is_readable($config->application->coreConfigDir . "config.ini")) {
//    $config_core = new ConfigIni($config->application->coreConfigDir . "config.ini");
//    //$config->merge($override);
//}
require $config->application->autoload;
//$core_config = new Ini($config->application->coreConfigDir . "config.ini");
// Регистрируем автозагрузчик
$loader = new Loader();

check_log($config->log->main);
check_log($config->log->import);
check_log($config->log->track);
$logger = new FileAdapter($config->log->main);


$loader->registerDirs(
        [
            $config->application->controllersDir,
            $config->application->modelsDir,
            $config->application->pluginsDir,
            $config->application->libraryDir,
            $config->application->EntitysDir,
            //$coreDir . $config_core->application->controllersDir,
            //$coreDir . $config_core->application->modelsDir,
            //$coreDir . $config_core->application->pluginsDir,
            //$coreDir . $config_core->application->libraryDir,
            //$coreDir . $config_core->application->EntitysDir
        ]
);

$loader->registerNamespaces([
    'Phalcon' => $config->application->incubatorDir
]);

$loader->registerClasses([
    'Services' => APP_PATH . 'app/Services.php'
]);

try {
    // Обрабатываем запрос
    $loader->register();
} catch (\Exception $e) {
    echo "Exception: ", $e->getMessage();
}



// Создаем DI
$di = new FactoryDefault();

$di->set(
        "voltService", function ($view, $di) use ($config) {
    $volt = new Volt($view, $di);
    $volt->setOptions(
            [
                "compiledPath" => $config->application->voltDir,
                "compiledExtension" => ".compiled",
            ]
    );
    return $volt;
}
);

$di->set(
        "phpService", function ($view, $di) {
    $php = new Php($view, $di);
    return $php;
}
);

// Настраиваем компонент View
$di->set(
        "view", function () use ($config) {
    $view = new View();
    $view->setViewsDir($config->application->viewsDir);
    $view->registerEngines(
            [
                ".volt" => "voltService",
                ".phtml" => "phpService"
            ]
    );
    return $view;
}
);
$di->set(
    "viewSimple", function () use ($config) {
    $view = new Phalcon\Mvc\View\Simple();
    $view->registerEngines(
        [
            ".ini" => "voltService"
        ]
    );
    return $view;
});

// Настраиваем базовый URI так, чтобы все генерируемые URI содержали директорию "tutorial"
$di->set(
        "url", function () {
    $url = new Url();
    return $url;
}
);
$di->setShared('config', $config);
// Initialise the mongo DB connection.
$di->setShared('mongo', function () {
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
});

// Collection Manager is required for MongoDB
$di->setShared('collectionManager', function () {
    return new Manager();
});

$di->setShared('session', function () {
    $config = $this->getShared('config');
    $path = $config->session->path;
    $lifetime = (integer)$config->session->lifetime;
    ini_set('session.gc_maxlifetime', $lifetime);
    ini_set('session.save_path',$path);
    $session = new SessionAdapter(['lifetime'=>$lifetime]);
    $session->start();
    $session->destroy();
    return $session;
});

$di->set(
        "dispatcher", function () {
    // Создаем менеджер событий
    $eventsManager = new EventsManager();
    // Плагин безопасности слушает события, инициированные диспетчером
    $eventsManager->attach(
            "dispatch:beforeExecuteRoute", new SecurityPlugin()
    );

    // Отлавливаем исключения и not-found исключения, используя NotFoundPlugin
    $eventsManager->attach(
            "dispatch:beforeException", new NotFoundPlugin()
    );

    $dispatcher = new Dispatcher();

    // Связываем менеджер событий с диспетчером
    $dispatcher->setEventsManager($eventsManager);

    return $dispatcher;
}
);

Phalcon\Tag::setTitle($config->application->title);

if (!(bool) $config->application->debug) {
    $logger->setLogLevel(
            Logger::INFO
    );
}
$di->setShared('logger', $logger);

$logger_import = new FileAdapter($config->log->import);
if (!$config->application->debug) {
    $logger_import->setLogLevel(
            Logger::INFO
    );
}
$di->setShared('logger_import', $logger_import);

$track_logger = new FileAdapter($config->log->track);
$di->setShared('track_logger', $track_logger);
$di->set(
        "flash", function () {
    $flash = new FlashSession(
            [
        "error" => "alert alert-danger",
        "success" => "alert alert-success",
        "notice" => "alert alert-info",
        "warning" => "alert alert-warning",
            ]
    );

    return $flash;
}
);

$application = new Application($di);

try {
    // Обрабатываем запрос
    $response = $application->handle();

    $response->send();
} catch (\Exception $e) {
    echo "Exception: ", $e->getMessage();
}