<?php
$_SERVER['PATH_INFO'] = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
$_COOKIE['themeMode'] = $_COOKIE['themeMode'] ?? 'dark';
// Display errors.
ini_set('display_errors', 'off');
// Reporting all.
error_reporting(0);
header("Access-Control-Allow-Origin: *");
// Defined encoding
header("Content-Type: text/html;charset=utf-8");
const DS = DIRECTORY_SEPARATOR;
defined('EXCHANGE_DIR') or define('EXCHANGE_DIR' , dirname(__FILE__, 2));
defined('CODEBASE_DIR') or define('CODEBASE_DIR', EXCHANGE_DIR . DS.'codebase');
defined('WEBSERVER_DIR') or define('WEBSERVER_DIR', getcwd());
const CODONO_VERSION = '7.2.5';
// Application path
const APP_PATH = CODEBASE_DIR.DIRECTORY_SEPARATOR.'Application'.DIRECTORY_SEPARATOR;
// DB Backup Path
const DATABASE_PATH = CODEBASE_DIR.DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR;

// Cache path
const RUNTIME_PATH = WEBSERVER_DIR.DIRECTORY_SEPARATOR.'Runtime'.DIRECTORY_SEPARATOR;
// Upload Images Path
const UPLOAD_PATH = '.'.DIRECTORY_SEPARATOR.'Upload'.DIRECTORY_SEPARATOR;


if (file_exists(CODEBASE_DIR.DIRECTORY_SEPARATOR.'pure_config.php') && file_exists(CODEBASE_DIR.DIRECTORY_SEPARATOR.'other_config.php')) {
    include_once(CODEBASE_DIR.DIRECTORY_SEPARATOR.'pure_config.php');
	include_once(CODEBASE_DIR.DIRECTORY_SEPARATOR.'other_config.php');
} else {
    die('Your Exchange is\'nt setup properly , Please look into config');
}

require CODEBASE_DIR.DIRECTORY_SEPARATOR.'Framework'.DIRECTORY_SEPARATOR.'codono.php';
