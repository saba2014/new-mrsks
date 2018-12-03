<?php

declare(strict_types=1);

use Phalcon\Di\FactoryDefault\Cli as CliDI;
use Phalcon\Cli\Console as ConsoleApp;
use Phalcon\Loader;
use Phalcon\Config\Adapter\Ini as ConfigIni;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\File as FileAdapter;

// Используем стандартный для CLI контейнер зависимостей
$di = new CliDI();


/**
 * Регистрируем автозагрузчик, и скажем ему, чтобы зарегистрировал каталог задач
 */
$loader = new Loader();
define('APP_PATH', realpath('..') . '/');
// Загружаем файл конфигурации, если он есть
$config = new ConfigIni(APP_PATH . "var/config/config.ini");
require $config->application->autoload;

$di->set("config", $config);

$loader->registerDirs(
    [
        APP_PATH . "app/cli/tasks",
        $config->application->modelsDir,
        $config->application->pluginsDir,
        $config->application->libraryDir,
        $config->application->EntitysDir
    ]
);
$loader->registerNamespaces([
    'Phalcon' => $config->application->incubatorDir
]);
$loader->register();


$logger_import = new FileAdapter($config->log->import);
 $logger= new FileAdapter($config->log->main);
if (!$config->application->debug) {
    $logger->setLogLevel(
        Logger::INFO
    );
}
$di->setShared('logger', $logger);
$di->setShared('logger_import', $logger_import);
// Создаем консольное приложение
$console = new ConsoleApp();

$console->setDI($di);


/**
 * Определяем консольные аргументы
 */
$arguments = [];

foreach ($argv as $k => $arg) {
    if ($k === 1) {
        $arguments["task"] = $arg;
    } elseif ($k === 2) {
        $arguments["action"] = $arg;
    } elseif ($k >= 3) {
        $arguments["params"][] = $arg;
    }
}


try {
    // обрабатываем входящие аргументы
    $console->handle($arguments);
} catch (\Phalcon\Exception $e) {
    echo $e->getMessage();

    exit(255);
}
