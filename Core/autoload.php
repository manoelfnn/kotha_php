<?php

/*
function frameworkAutoload($_className)
{
    $_className = str_replace('\\', '/', $_className);
    $include = APP_DIR . '/' . $_className . '.php';
    if (file_exists($include)) {
        include $include;
        return;
    }
    $include = SYSTEM_DIR . '/' . $_className . '.php';
    if (file_exists($include)) {
        include $include;
        return;
    }
}*/


//if (config_item('COMPOSER') === true) {
    $file = BASE_DIR . '/vendor/autoload.php';
    file_exists($file) ? include_once $file : false;
//}


//spl_autoload_register("frameworkAutoload", true, true);
