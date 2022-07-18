<?php
namespace Core;

abstract class Cookie
{
    public static function get($_name)
    {
        return isset($_COOKIE[$_name]) ? $_COOKIE[$_name] : null;
    }

    public static function set($_name, $_value, $_time = 0)
    {
        $cookie = config_item('COOKIE');
        setcookie($_name, $_value, $_time, $cookie['PATH'], $cookie['DOMAIN'], $cookie['SECURE'], $cookie['HTTPONLY']);
    }

}