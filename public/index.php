<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));
define('APP_ROOT', dirname(__DIR__));
define('APP_NAME', 'Tersus');
define('LIB_DIR', APP_ROOT . '/lib');

// Setup autoloading
require 'init_autoloader.php';

$appConfig = require 'config/application.config.php';

Zend\Mvc\Application::init($appConfig)->run();