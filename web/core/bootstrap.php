<?php

defined('DEBUG_MODE') or define('DEBUG_MODE', false);
defined('BASE_PATH') or die('Undefined BASE_PATH');
defined('APP_PATH') or die('Undefined APP_PATH');

define('DEFAULT_CONTROLLER_NAME', 'home');
define('DEFAULT_ACTION_NAME', 'index');

define('CORE_PATH', __DIR__ . DIRECTORY_SEPARATOR);
require CORE_PATH . 'constant.php';
require CORE_PATH . 'class/Application.class.php';
$theApp = new \system\Application();
$theApp->run();
