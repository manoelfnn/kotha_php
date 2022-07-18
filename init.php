<?php

use Core\DB;
use Core\Route;

$framework_time_start = microtime(true);

define('BASE_DIR', $BASE_DIR);
define('PUBLIC_DIR', BASE_DIR . '/public');
define('APP_DIR', BASE_DIR . '/app');
define('APP_VIEWS', APP_DIR . '/views');

define('SYSTEM_DIR', __DIR__);
define('CORE_DIR', __DIR__ . '/Core');

define('EOL', "\n");
define('TAB', "\t");

define('DEV', 1);
define('PRO', 0);

function isLocalHost()
{
    // return in_array($_SERVER['HTTP_HOST'], array_merge($_CONFIG['LOCALHOSTS'], ['127.0.0.1', 'localhost']));
    return in_array($_SERVER['SERVER_ADDR'], array_merge(config_item('LOCALHOSTS'), ['::1', '127.0.0.1']));
}

include(BASE_DIR . '/config.php');

function config_item($_item, $_sub = null)
{
    global $_CONFIG;
    if ($_sub && isset($_CONFIG[$_sub]) && isset($_CONFIG[$_sub][$_item])) {
        return $_CONFIG[$_sub][$_item];
    } elseif (isset($_CONFIG[$_item])) {
        return $_CONFIG[$_item];
    }
    return "";
}

if (config_item('HTTPS')) {
    header("strict-transport-security: max-age=600");
    if (!(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) {
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect);
        exit();
    }
}
if (config_item('WWW')) {
    $host = explode('.', $_SERVER['HTTP_HOST']);
    // site.com - 2 - add www.
    // www.site.com - 3
    // site.com.br - 3 - add www.
    // www.site.com.br - 3
    // demo.site.com.br -3 

    if (count($host) == 2 || (count($host) == 3 && $host[0] != 'www')) {
        //if (substr($_SERVER['HTTP_HOST'], 0, 4) !== 'www.') {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . (@$_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . 'www.' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        exit;
    }
}

include CORE_DIR . '/utils.php';
include CORE_DIR . '/autoload.php';

DB::setEngine();

if (isset($_CONFIG['INIT']) && $_CONFIG['INIT']) {
    $_CONFIG['INIT']();
}

if (config_item('ENV') == DEV) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
    if (version_compare(PHP_VERSION, '5.3', '>=')) {
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
    } else {
        error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
    }
}

if (config_item('ERROR_LOG')) {
    ini_set('log_errors', 1);
    ini_set('error_log', config_item('ERROR_LOG'));
}

if (!$_CONFIG['COOKIE']['DOMAIN']) {
}

$session = config_item('SESSION');
$cookie = config_item('COOKIE');
session_set_cookie_params($session['EXPIRATION'], $cookie['PATH'], $cookie['DOMAIN'], $cookie['SECURE'], $cookie['HTTPONLY']);

ini_set('session.gc_maxlifetime', $session['EXPIRATION']);
// ini_set('session.cookie_path', $cookie['PATH']);
session_name($session['COOKIE_NAME']);


session_start();

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set(config_item('TIME_ZONE'));

Route::run();

if (config_item('BENCHMARK') && !isProduction()) {
    echo "
    <div onclick='this.remove()' id='benchmark-div-float' style='padding: 10px; cursor: pointer; opacity: 0.5; top: 2px; right: 2px; background-color: #000; font-size: 11px; color: #FFF; position: fixed; width: auto; z-index: 10000000;'>
        Execution time: " . number_format((microtime(true) - $framework_time_start), 4) . " s<br>
        Peak Usage Memory: " . size_convert(memory_get_peak_usage()) . "
    </div>
    ";
}
