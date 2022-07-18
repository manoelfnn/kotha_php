<?php

/*

Ex.
    1. http://localhost/
        1º - NAMESPACE \ DEFAULT_CONTROLLER                         // tem um controller padrão?
        2º - OPTIONAL_NAMESPACE \ DEFAULT_CONTROLLER                // tem um controller padrão?
        3º - status(404)

    2. http://localhost/xxx
        1º - NAMESPACE \ xxx                                        // é um controller?
        2º - OPTIONAL_NAMESPACE \ xxx                               // é um controller?
        3º - NAMESPACE \ DEFAULT_CONTROLLER -> xxx                  // é um método do controller padrão?
        4º - OPTIONAL_NAMESPACE \ DEFAULT_CONTROLLER -> xxx         // é um método do controller padrão?
        5º - status(404)

    3. http://localhost/xxx/yyy
        1º - NAMESPACE \ xxx -> yyy                                 // é um método do controller "xxx"?
        2º - OPTIONAL_NAMESPACE \ xxx -> yyy                        // é um método do controller "xxx"?
        3º - NAMESPACE \ DEFAULT_CONTROLLER -> xxx(yyy)             // é um parâmetro do método "xxx" do controller padrão?
        4º - OPTIONAL_NAMESPACE \ DEFAULT_CONTROLLER -> xxx(yyy)    // é um parâmetro do método "xxx" do controller padrão?
        5º - status(404)        


*/

namespace Core;

use Core\App;
use Core\HTMLView;
use ReflectionMethod;
use Core\ErrorHandler;
use Exception;

use function PHPSTORM_META\type;

abstract class Route
{
    private static $userRouterList = [];
    private static $url = null;
    private static $requestMethod;
    private static $controller = '';
    private static $defaultController = '';
    private static $method = 'index';
    private static $namespace = 'App\Controllers';
    private static $optionalNamespace = null;

    public static function run()
    {

        $namespace = config_item('NAMESPACE');
        self::$namespace = (is_callable($namespace) ? $namespace() : $namespace) . '\\';

        $optionalNamespace = config_item('OPTIONAL_NAMESPACE');
        self::$optionalNamespace = (is_callable($optionalNamespace) ? $optionalNamespace() : $optionalNamespace) . '\\';

        $defaultController = config_item('DEFAULT_CONTROLLER');
        self::$defaultController = (is_callable($defaultController) ? $defaultController() : $defaultController) . 'Controller';

        self::$controller = self::$namespace . self::$defaultController;

        $routes = config_item('ROUTE');
        foreach ($routes as $route => $call) {
            $requestMethod = is_array($call) && isset($call['request_method']) ? $call['request_method'] : 'ALL';
            self::add($route, $call, $requestMethod);
        }

        if (self::$url != null) {
            return;
        }

        $path_app = parse_url(App::getUrl()); // APP_URL = http://www.aplication.com/app1
        $path_app = isset($path_app['path']) ? $path_app['path'] : ''; // /app1
        //REQUEST_URI = http://www.aplication.com/app1/controller/method
        self::$url = substr(str_replace($path_app, '', $_SERVER['REQUEST_URI']), 1); // URL = controller/method

        if (($pos = strpos(self::$url, '?')) !== false) {
            self::$url = substr(self::$url, 0, $pos);
        }
        self::$requestMethod = $_SERVER['REQUEST_METHOD'];
        $userRouteIndex = self::urlIsUserRoute();
        if ($userRouteIndex !== false) {
            self::processUserRouter($userRouteIndex);
        } else {
            self::processNormalRouter();
        }
    }

    static private function urlIsUserRoute()
    {
        foreach (self::$userRouterList as $index => $userRoute) {
            $method = ($userRoute["method"] == "ALL" ? true : $userRoute["method"] == self::$requestMethod);
            if ($method && preg_match($userRoute["pattern"], self::$url, $matches)) {
                array_shift($matches);
                self::$userRouterList[$index]["args"] = $matches;
                return $index;
            }
        }
        return false;
    }

    static private function processUserRouter($_userRouteIndex)
    {
        $route = self::$userRouterList[$_userRouteIndex];
        $call = $route['call'];

        $controller = '';
        $method = '';
        $args = [];

        if (is_string($call)) {
            $controller = $call;
        } elseif (is_callable($call)) {
            $controller = call_user_func_array($call, $route['args']);
            if (!$controller)
                return;
        } elseif (is_array($call)) {
            $controller = isset($call['controller']) ? $call['controller'] : '';
            $method = isset($call['method']) ? $call['method'] : '';
            $args = isset($call['args']) ? $call['args'] : '';
            $isController = isset($call['is_controller']) ? $call['is_controller'] : true;

            if (isset($call['namespace'])) {
                self::$namespace = $call['namespace'] . '\\';
                self::$optionalNamespace = $call['optional_namespace'] . '\\';
            }

            if (isset($call['append_namespace'])) {
                self::$namespace = self::$namespace . $call['append_namespace'] . '\\';;
                self::$optionalNamespace = self::$optionalNamespace . $call['append_namespace'] . '\\';
            }

            self::$defaultController = isset($call['default_controller']) ? $call['default_controller'] . 'Controller' : self::$defaultController;

            if (!$isController) {
                $args = explode("/", $route['args'][0]);
                array_shift($args);
                if (count($args)) {
                    $controller = array_shift($args);
                    if (count($args)) {
                        $method = array_shift($args);
                    }
                }
            }
        }
        self::callControllerMethod($controller, $method, $args);
    }

    static private function processNormalRouter()
    {
        $controller = '';
        $method = '';

        $urlParts = explode('/', self::$url);
        if (isset($urlParts[0]) && $urlParts[0]) {
            $controller = $urlParts[0];
            unset($urlParts[0]);
            if (isset($urlParts[1]) && $urlParts[1]) {
                $method = $urlParts[1];
                unset($urlParts[1]);
            }
        }
        // Quando tiver "/" no final da url gerará um item vazio no final do array, então removemos este item.
        end($urlParts) == '' && array_pop($urlParts);
        self::callControllerMethod($controller, $method, array_values($urlParts));
    }

    static private function callControllerMethod($_controller, $_method, $_args = [])
    {
        if ($_controller) {
            $tmpControllerName = self::$namespace . ucfirst($_controller) . 'Controller';
            $tmpControllerNameOptional = self::$optionalNamespace . ucfirst($_controller) . 'Controller';
            // Verifica se existe um controller no namespace PADRÃO
            if (class_exists($tmpControllerName)) {
                self::$controller = $tmpControllerName;
                self::$method = $_method ? $_method : self::$method;
                // senão, verifica se existe um controller no namespace OPCIONAL
            } elseif (class_exists($tmpControllerNameOptional)) {
                self::$controller = $tmpControllerNameOptional;
                self::$method = $_method ? $_method : self::$method;
            } else {
                // senão, busca-se o controller padrão, ou no namespace PADRÃO, ou no OPCIONAL
                $tmpControllerName = self::$namespace . self::$defaultController;
                self::$controller = class_exists($tmpControllerName) ? $tmpControllerName : self::$optionalNamespace . self::$defaultController;
                self::$method = $_controller;
                if ($_method) {
                    array_unshift($_args, $_method);
                }
            }
        } else {
            // senão, busca-se o controller padrão, ou no namespace PADRÃO, ou no OPCIONAL
            $tmpControllerName = self::$namespace . self::$defaultController;
            self::$controller = class_exists($tmpControllerName) ? $tmpControllerName : self::$optionalNamespace . self::$defaultController;
        }


        $controllerInstance = new self::$controller();
        if (method_exists($controllerInstance, self::$method)) {
            $reflectionMethod = new ReflectionMethod(self::$controller, self::$method);
            if (!$reflectionMethod->isPublic() || substr(self::$method, 0, 2) == "__") {
                throw new Exception("Não é possível acessar o método protegido " . self::$controller . "->" . self::$method . "().");
            }
            $parameters = [];
            $totalArgs = count($_args);
            foreach ($reflectionMethod->getParameters() as $param) {
                $parameters[] = '$' . $param->name . ($param->isOptional() ? '=' . gettype($param->getDefaultValue()) : '');
            }
            if ($totalArgs < $reflectionMethod->getNumberOfRequiredParameters()) {
                throw new Exception("Faltam parâmetros para chamar o método " . self::$controller . "->" . self::$method . "(" . implode(", ", $parameters) . ").");
            } elseif ($totalArgs > count($reflectionMethod->getParameters())) {
                throw new Exception("Muitos parâmetros para chamar o método " . self::$controller . "->" . self::$method . "(" . implode(", ", $parameters) . ").");
            }
            call_user_func_array([$controllerInstance, self::$method], $_args);
        } elseif (method_exists($controllerInstance, '__fallback')) {
            call_user_func_array([$controllerInstance, '__fallback'], []);
        } else {
            throw new Exception("Método " . self::$controller . "->" . self::$method . "() não existe.");
        }
    }

    static private function add($_url, $_call, $_requestMethod = 'ALL')
    {
        $pattern = '@^' . preg_replace('@{([a-zA-Z0-9\_\-]+)}@', '([a-zA-Z0-9\-\_]+)', $_url) . '$@D';
        self
            ::$userRouterList[] = array(
            'method' => $_requestMethod,
            'url' => $_url,
            'pattern' => $pattern,
            'call' => $_call
        );
    }

    public static function get($_url, $_call)
    {
        self::add($_url, $_call, "GET");
    }

    public static function post($_url, $_call)
    {
        self::add($_url, $_call, "POST");
    }

    public static function all($_url, $_call)
    {
        self::add($_url, $_call, "ALL");
    }

    public static function getUrl()
    {
        return self::$url;
    }

    public static function setUrl($_url)
    {
        self::$url = $_url;
    }

    public static function getController()
    {
        return self::$controller;
    }

    public static function getmethod()
    {
        return self::$method;
    }
}
