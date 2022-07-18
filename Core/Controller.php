<?php
namespace Core;

class Controller
{

    private $params;

    private $methodName;

    public function __construct()
    {
    }

    public function redirect($location)
    {
        header("location: " . $location);
        exit();
    }

    public function getControllerUrl($add = null)
    {
        return BASE_URL . "/" . $this->getControllerName() . ($add ? "/" . $add : "");
    }

    public function getMethodUrl($add = null)
    {
        return BASE_URL . "/" . $this->getControllerName() . ($this->methodName ? "/" . $this->methodName : "") . ($add ? "/" . $add : "");
    }


    public function getControllerName()
    {
        $controller = Route::getControllerName();
        $controller = str_replace("Controller", "", $controller);
        return $controller;
    }

    public function getMethodName()
    {
        return Route::getMethod();
    }
}

?>