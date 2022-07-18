<?php
namespace Core;

abstract class ErrorHandler
{
    public static function display($_msg){
        if(config_item('ENV') == DEV) {
            trigger_error($_msg, E_USER_ERROR);
        } else {
            header('HTTP/1.0 500 Internal Server');
        }
        exit;
    }

}