<?php
namespace Core;

abstract class Session
{
    public static function get($_name)
    {
        return isset($_SESSION[$_name]) ? $_SESSION[$_name] : '';
    }

    public static function set($_name, $_value)
    {
        $_SESSION[$_name] = $_value;
    }

}