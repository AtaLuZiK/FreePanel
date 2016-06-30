<?php
namespace system;
defined('BASE_PATH') || exit('No direct script access allowed');

class Application
{
    private $config = array();
    private $requestParameters = array();
    
    
    public function run()
    {
        require CORE_PATH . 'function/common.php';
        // set exception handlers
        DEBUG_MODE ? error_reporting(E_ALL) : error_reporting(E_ALL ^ E_WARNING);
        spl_autoload_register(__CLASS__ . '::autoload');
        //register_shutdown_function(__CLASS__ . '::handleFatal');
        set_error_handler(array($this, 'handleError'));
        set_exception_handler(array($this, 'handleException'));
        
        $this->loadConfig(CORE_PATH . 'config' . DIRECTORY_SEPARATOR . 'global.php');
        $this->loadConfig(APP_PATH . 'config' . DIRECTORY_SEPARATOR . 'global.php');
        date_default_timezone_set($this->getConfig('TIMEZONE', 'Asia/Shanghai'));
        $this->dispatchRouter();
    }
    
    
    private function dispatchRouter()
    {
        $this->requestParameters = array(
            'get' => &$_GET,
            'post' => &$_POST,
            'server' => &$_SERVER,
            'cookie' => &$_COOKIE,
            'request' => &$_REQUEST
        );
        define('URL_MODE', $this->getConfig('URL_MODE', URL_MODE_PATHINFO));
        define('REQUEST_METHOD', strtolower($_SERVER['REQUEST_METHOD']));
        define('IS_GET', REQUEST_METHOD == 'get');
        define('IS_POST', REQUEST_METHOD === 'post');
        define('IS_AJAX', $this->getRequestParameter('server.HTTP_X_REQUESTED_WITH', null, 'strtolower') === 'xmlhttprequest');
        
        // /controller/action/others
        $this->parseRequestUri();
        $this->load(CONTROLLER_NAME, ACTION_NAME);
    }
    
    
    /**
     *
     * @param string $name
     * @param mixed $default
     * @param string/array $filters
     * @return mixed
     * @example
     * getRequestParameter('id');
     * getRequestParameter('post.name', 'my name');
     * getRequestParameter('server.', null, 'strtolower');
     */
    public function getRequestParameter($name, $default = null, $filters = null)
    {
        if (strpos($name, '.')) {
            list($method, $name) = explode('.', $name, 2);
        } else {
            $method = 'param';
        }
        
        if ($method == 'param') {
            switch (REQUEST_METHOD) {
                case 'get':
                case 'post':
                    $method = REQUEST_METHOD;
                    break;
                default:
                    $method = 'request';
            }
        }
        if (!isset($this->requestParameters[$method]))
            return null;
        $data = null;
        if (empty($name)) {
            $data = $this->requestParameters[$method];
        } else {
            $data = isset($this->requestParameters[$method][$name]) ? $this->requestParameters[$method][$name] : $default;
        }
        
        if (!empty($filters)) {
            if (is_string($filters)) {
                if (strpos($filters, '|')) {
                    $filters = explode('|', $filters);
                } else {
                    $filters = [ $filters ];
                }
            }
            foreach ($filters as $filter) {
                if (!function_exists($filter))
                    continue;
                if (is_array($data)) {
                    foreach ($data as $key => $value) {
                        $data[$key] = $filter($value);
                    }
                } else {
                    $data = $filter($data);
                }
            }
        }
        
        return $data;
    }
    
    
    public function load($controllerName = '', $actionName = '')
    {
        if (empty($controllerName)) {
            $controllerName = CONTROLLER_NAME;
        }
        if (empty($actionName)) {
            $actionName = ACTION_NAME;
        }
        
        $controllerName[0] = chr(ord($controllerName[0]) & 0x5F);
        $controllerName = $controllerName . 'Controller';
        
        // invoke action
        $this->loadUserController($controllerName);
        $controllerClass = new $controllerName();
        method_exists($controllerClass, '_before') && call_user_func_array(array($controllerClass, '_before'), array($actionName));
        if (method_exists($controllerClass, $actionName)) {
            method_exists($controllerClass, '_before' . $actionName) && call_user_func_array(array($controllerClass, '_before' . $actionName), array());
            call_user_func_array(array($controllerClass, $actionName), array());
            method_exists($controllerClass, '_after' . $actionName) && call_user_func_array(array($controllerClass, '_after' . $actionName), array());
        } else {
            call_user_func_array(array($controllerClass, '_empty'), array($actionName));
        }
        method_exists($controllerClass, '_after') && call_user_func_array(array($controllerClass, '_before'), array($actionName));
    }
    
    
    private function parseRequestUri()
    {
        define('REQUEST_SCHEME', $_SERVER['REQUEST_SCHEME']);
        define('HTTP_HOST', $_SERVER['HTTP_HOST']);
        define('BASE_URL', '//' . HTTP_HOST);
        define('BASE_SCHEME_URL', REQUEST_SCHEME . ':' . BASE_URL);
        $uri = '';
        
        $uri = $this->getRequestParameter('server.PATH_INFO', '/');
        if (URL_MODE == URL_MODE_PATHINFO) {
            define('ENTRY_URL', BASE_URL . $_SERVER['SCRIPT_NAME']);
        } else {
            define('ENTRY_URL', BASE_URL);
        }
        $routes = explode('/', ltrim($uri, '/'), 3);
        define('CONTROLLER_NAME', !empty($routes[0]) ? $routes[0] : $this->getConfig('DEFAULT_CONTROLLER', DEFAULT_CONTROLLER_NAME));
        define('ACTION_NAME', !empty($routes[1]) ? $routes[1] : DEFAULT_ACTION_NAME);
    }
    
    
    /**
     * load the configure from current module
     * @param string $filename
     */
    private function loadConfig($filename)
    {
        if (file_exists($filename)) {
            $this->config = array_merge(include $filename, $this->config);
            if (array_key_exists('LOAD_EXT_CONFIG', $this->config)) {
                foreach ($this->config['LOAD_EXT_CONFIG'] as $prefix => $name) {
                    $filename = APP_PATH . 'config' . DIRECTORY_SEPARATOR . $name . '.php';
                    if (file_exists($filename)) {
                        is_string($prefix) ? $this->config[$prefix] = include $filename : $this->config = array_merge(include $filename, $this->config);
                    }
                }
            }
        } else {
            // TODO: there would be show some warning in debug mode
        }
    }
    
    
    /**
     * get the configure value from specify name.
     * @param string $name the name of the configure value
     * @param mixed $default
     * @return mixed
     * @example
     * getConfig('DEFAULT_CONTROLLER');
     * getConfig('Database.HOST', '127.0.0.1');
     */
    public function getConfig($name = null, $default = null)
    {
        if (empty($name)) {
            return $this->config;
        } else if (is_string($name)) {
            if (!strpos($name, '.')) {
                return isset($this->config[$name]) ? $this->config[$name] : $default;
            }
            $name = explode('.', $name);
            return isset($this->config[$name[0]][$name[1]]) ? $this->config[$name[0]][$name[1]] : $default;
        }
        
        return null;
    }
    
    
    public static function autoload($className)
    {
        if (($pos = strpos($className, '\\')) !== FALSE) {
            $namespace = substr($className, 0, $pos);
            $className = substr($className, $pos + 1);
            if ($namespace == 'system') {
                self::loadCoreClass($className);
                return;
            }
        }
        $searchPath = array(
            CORE_PATH . 'library' . DIRECTORY_SEPARATOR,
            array(__CLASS__, 'loadUserController'),
            APP_PATH . 'model' . DIRECTORY_SEPARATOR,
            APP_PATH . 'common' . DIRECTORY_SEPARATOR,
        );
        
        foreach ($searchPath as $path) {
            if (is_string($path)) {
                $filename = $path . $className . '.class.php';
                if (file_exists($filename)) {
                    include $filename;
                    break;
                }
            } else if (is_array($path) && is_callable($path)) {
                call_user_func($path, $className);
                if (class_exists($className))
                    break;
            }
        }
        
        if (!class_exists($className)) {
            $searchPathText = "\t" . CORE_PATH . 'class' . DIRECTORY_SEPARATOR . PHP_EOL;
            foreach ($searchPath as $path) {
                if (is_string($path)) {
                    $searchPathText = $searchPathText . "\t" . $path;
                } else if (is_array($path) && is_callable($path)) {
                    $searchPathText = $searchPathText . "\t" . $path[0] . '::' . $path[1];
                }
                $searchPathText = $searchPathText . PHP_EOL;
            }
            $message = 'Could not found class named ' . $className . PHP_EOL
               . 'Search path: ' . PHP_EOL
               . $searchPathText;
            throw new ClassNotFoundException($message);
        }
    }
    
    
    public static function loadCoreClass($className)
    {
        $filename = CORE_PATH . 'class' . DIRECTORY_SEPARATOR . $className . '.class.php';
        if (file_exists($filename)) {
            include $filename;
        }
    }
    
    
    public static function loadUserController($className)
    {
        $filename = APP_PATH . 'controller' . DIRECTORY_SEPARATOR . "$className.class.php";
        if (file_exists($filename)) {
            include $filename;
        }
    }
    
    
    public static function handleFatal()
    {
        
    }
    
    
    public function handleError($errno, $errmsg, $errfile, $errline)
    {
        if ($errno === E_WARNING) {
            // ignore warning always
            // TODO: save warning in stack, and use float layer show it if possible
            return;
        }
        if (!(error_reporting() & $errno))  // This error code is not included in error_reporting
            return;
        $message = "$errmsg ($errfile, Line: $errline)";
        self::showErrorPage($message);
    }
    
    
    public function handleException($exception)
    {
        $error = array(
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'backtrace' => $exception->getTraceAsString()
        );
        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');
        $this->showErrorPage($error);
    }
    
    
    public function showErrorPage($error)
    {
        $e = array();
        if (DEBUG_MODE) {
            if (!is_array($error)) {
                $trace = debug_backtrace();
                $e['message'] = $error;
                $e['file'] = $trace[1]['file'];
                $e['line'] = $trace[1]['line'];
                ob_start();
                debug_print_backtrace();
                $e['backtrace'] = ob_get_clean();
            } else {
                $e = $error;
            }
        } else {
            $errorUrl = $this->getConfig('ERROR_URL');
            if (!empty($errorUrl)) {
                redirect($errorUrl);
            }
        }
        
        if (ob_get_length() > 0)
            ob_end_clean();
        $errorTemplate = $this->getConfig('ERROR_TEMPLATE', '@system/error');
        $template = new Template();
        $template->assign('error', $e);
        $template->show($errorTemplate);
        exit();
    }
    
    
    private function __clone()
    {
        
    }
}
