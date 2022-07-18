<?php

namespace Core;

abstract class Auth
{
    static private $loginURL = '';
    public static $authName = 'auth';

    public static function setName($_name)
    {
        static::$authName = $_name;
    }

    public static function setLoginUrl($_loginURL = '')
    {
        self::$loginURL = $_loginURL;
    }

    public static function isLogged()
    {
        return isset($_SESSION[config_item('NAME', 'SESSION')][static::$authName]) ? true : false;
    }

    public static function logIn($_data, $_redirect = null)
    {
        self::setData($_data);
        if ($_redirect) {
            redirect($_redirect);
        }
    }

    public static function logOut($_redirect = null)
    {
        unset($_SESSION[config_item('NAME', 'SESSION')][static::$authName]);
        self::check($_redirect);
    }

    public static function logOutNoRedirect()
    {
        unset($_SESSION[config_item('NAME', 'SESSION')][static::$authName]);
    }

    public static function getData()
    {
        self::check();
        return $_SESSION[config_item('NAME', 'SESSION')][static::$authName];
    }

    public static function setData($_data)
    {
        $_SESSION[config_item('NAME', 'SESSION')][static::$authName] = $_data;
    }

    public static function check($_loginUrl = null, $_callbackCheck = null)
    {
        if ($_loginUrl)
            self::setLoginUrl($_loginUrl);

        if (!self::isLogged()) {
            if (is_callable($_callbackCheck) && $_callbackCheck()) {
                return;
            }
            header(self::$loginURL ? 'location: ' . self::$loginURL : 'HTTP/1.1 403 Forbidden');
            exit();
        }
    }
}
