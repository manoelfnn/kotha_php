<?php
namespace Core;

abstract class App
{
    static private function __isAnchor($_url, $_text = null, $_title = null, $_class = null)
    {
        return $_text ? anchor($_url, $_text, $_title, $_class) : $_url;
    }

    public static function getPublicUrl($_add = null, $_text = null, $_title = null, $_class = null)
    {
        return self::__isAnchor(config_item('BASE_URL') . "/public" . ($_add ? $_add : ''), $_text, $_title, $_class);
    }

    public static function getUrl($_add = null, $_text = null, $_title = null, $_class = null)
    {
        return self::__isAnchor(config_item('BASE_URL') . ($_add ? $_add : ''), $_text, $_title, $_class);
    }

    public static function getName()
    {
        return config_item('APP_NAME');
    }

    public static function getDomain()
    {
        //     return config_item('APP_DOMAIN');
    }

    public static function getDir($_add = null)
    {
        return APP_DIR . ($_add ? $_add : '');
    }

    //    public static function isMethod($_method, $_trueReturn = true)
    //    {
    //        if ($_method == Route::getMethod()) {
    //            return true === $_trueReturn ? true : $_trueReturn;
    //        } else {
    //            return true === $_trueReturn ? false : '';
    //        }
    //    }
}
