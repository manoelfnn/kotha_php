<?php
namespace Core\Libs;

class Url
{
    public static function removeProtocol($_url)
    {
        return preg_replace("(^https?://)", "", $_url);
    }

    public static function getCurrentUrl($_only = false){
        return (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].($_only ? strtok($_SERVER["REQUEST_URI"], '?') : $_SERVER['REQUEST_URI']);
    }
    
}