<?php

namespace ProcessWire;

/**
 * Router.php
 *
 * Stuff taken from https://gist.github.com/clsource/dc7be74afcbfc5fe752c
 * and Example Code from @lostkobrakai
 * and some stuff I put in there by myself
 */

// $routesPath = "{$this->config->paths->site}api/Routes.php";

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/AppApiHelper.php';
require_once __DIR__ . '/DefaultRoutes.php';
require_once __DIR__ . '/Auth.php';

class Router extends WireData {
    public function go() {
        set_error_handler("ProcessWire\Router::handleError");
        set_exception_handler('ProcessWire\Router::handleException');
        register_shutdown_function('ProcessWire\Router::handleFatalError');

        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Headers: Content-Type, AUTHORIZATION, X-API-KEY');
            header('Access-Control-Allow-Credentials: true');
        }

        try {
            // $routes are coming from this file:
            $routesPath = $this->wire('modules')->AppApi->routes_path;
            if (is_string($routesPath) && !empty($routesPath) && substr($routesPath, -1) !== '/') {
                require_once wire('config')->paths->root . $routesPath;
            } else {
                require_once wire('config')->paths->site . 'api/Routes.php';
            }

            $flatUserRoutes = [];
            self::flattenGroup($flatUserRoutes, $routes);

            $flatDefaultRoutes = [];
            self::flattenGroup($flatDefaultRoutes, DefaultRoutes::get());

            $allRoutes = array_merge($flatUserRoutes, $flatDefaultRoutes);

            // create FastRoute Dispatcher:
            $router = function (\FastRoute\RouteCollector $r) use ($allRoutes) {
                foreach ($allRoutes as $key => $route) {
                    if (!is_array($route)) {
                        continue;
                    }
                    $method = $route[0];
                    $url = $route[1];

                    // add trailing slash if not present:
                    if (substr($url, -1) !== '/') {
                        $url .= '/';
                    }

                    $class = isset($route[2]) ? $route[2] : false;
                    $function = isset($route[3]) ? $route[3] : false;
                    $routeParams = isset($route[4]) ? $route[4] : [];

                    $r->addRoute($method, $url, [$class, $function, $routeParams]);
                }
            };

            $dispatcher = \FastRoute\simpleDispatcher($router);

            $httpMethod = $_SERVER['REQUEST_METHOD'];
            $url = $this->wire('sanitizer')->url(wire('input')->url);

            // strip /api from request url:
            $endpoint = $this->wire('modules')->AppApi->endpoint;

            // support / in endpoint url:
            $endpoint = str_replace('/', "\/", $endpoint);

            $regex = '/\/' . $endpoint . '\/?/';
            $url = preg_replace($regex, '/', $url);

            // add trailing slash if not present:
            if (substr($url, -1) !== '/') {
                $url .= '/';
            }

            $routeInfo = $dispatcher->dispatch($httpMethod, $url);

            // Routeinfo and Auth extracted. Router::handle will return the info that should be output
            $return = Router::handle($routeInfo);

            AppApi::sendResponse(200, $return);
        } catch (\Throwable $e) {
            // Show Exception as json-response and exit.
            self::handleException($e);
        }
    }

    public function handle($routeInfo) {
        if (!isset($routeInfo[0]) || $routeInfo[0] !== \FastRoute\Dispatcher::FOUND) {
            // Handle FastRoute-Errors:
            switch ($routeInfo[0]) {
                case \FastRoute\Dispatcher::NOT_FOUND:
                throw new AppApiException('Route not found', 404);
                case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                throw new AppApiException('Method not allowed', 405);
            }
        }

        if (!isset($routeInfo[1]) || !is_array($routeInfo[1])) {
            throw new AppApiException('Routehandler not set', 500);
        }
        $handler = $routeInfo[1];

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'];
                if (isset($handler[0]) && is_array($handler[0])) {
                    $allowedMethods = $handler[0];
                }
                header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods) . ', HEAD, OPTIONS');
            }

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }

            return;
        }

        if (!Auth::getInstance()->isApikeyValid()) {
            throw new AppApiException('Apikey not valid.', 401);
        }

        if (!isset($handler[0]) || !is_string($handler[0]) || !isset($handler[1]) || !is_string($handler[1]) || !isset($handler[2])) {
            throw new AppApiException('Routehandler not valid', 500);
        }
        $class = $handler[0];
        $methodName = strtoupper($handler[1]);
        $routeParams = $handler[2];

        if (!isset($routeInfo[2]) || !is_array($routeInfo[2])) {
            throw new AppApiException('Routevars not set', 500);
        }
        $vars = (object) $routeInfo[2];

        // Check for Auth-Tokens and log the user in (if valid):
        if (!isset($routeParams['handle_authentication']) || $routeParams['handle_authentication']) {
            Auth::getInstance()->handleAuthentication();
        }

        // Check if the route is only allowed for a specific application-id:
        if (!empty($routeParams['application']) && $routeParams['application'] !== Auth::getInstance()->getApplication()->getID()) {
            throw new AppApiException('Route not allowed for this application', 400);
        }

        if (!empty($routeParams['applications']) && is_array($routeParams['applications']) && in_array(Auth::getInstance()->getApplication()->getID(), $routeParams['applications'])) {
            throw new AppApiException('Route not allowed for this application', 400);
        }

        // Check if particular route does need auth:
        if (isset($routeParams['auth']) && $routeParams['auth'] === true && !$this->wire('user')->isLoggedIn()) {
            throw new AppApiException('User does not have authorization', 401);
        }

        // Check if the current user has one of the required roles for this route:
        if (isset($routeParams['roles']) && (is_array($routeParams['roles']) || $routeParams['roles'] instanceof WireArray)) {
            $roleFound = false;
            foreach ($routeParams['roles'] as $role) {
                if ($role instanceof Role && $this->wire('user')->hasRole($role->id)) {
                    $roleFound = true;
                    break;
                } elseif ((is_string($role) || is_integer($role)) && $this->wire('user')->hasRole($role)) {
                    $roleFound = true;
                    break;
                }
            }
            if (!$roleFound) {
                throw new AppApiException('User does not have one of the required roles for this route.', 403);
            }
        }

        // If the code runs until here, the request is authenticated
        // or the request does not need authentication

        if (@$this->wire('modules')->getConfig('AppApi', 'access_logging')) {
            $logdata = [];
            $logdata[] = Auth::getInstance()->getApplicationLog();
            $logdata[] = Auth::getInstance()->getApikeyLog();
            if (Auth::getInstance()->getTokenLog()) {
                $logdata[] = Auth::getInstance()->getTokenLog();
            }

            wire('log')->save(AppApi::logAccess, 'Successful request with: ' . implode(', ', $logdata));
        }

        // merge url $vars with params
        $vars = array_merge((array) Router::params(), (array) $vars);
        // $vars['auth'] = Auth::getInstance();

        // convert array to object:
        $vars = json_decode(json_encode($vars));

        $data = $class::$methodName($vars);

        return $data;
    }

    public function params($index = null, $default = null, $source = null) {
        // check for php://input and merge with $_REQUEST
        if ((isset($_SERVER['CONTENT_TYPE']) &&
            stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
            (isset($_SERVER['HTTP_CONTENT_TYPE']) &&
        stripos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false) // PHP build in Webserver !?
        ) {
            if ($json = json_decode(@file_get_contents('php://input'), true)) {
                $_REQUEST = array_merge($_REQUEST, $json);
            }
        }

        $src = $source ? $source : $_REQUEST;

        return Router::fetch_from_array($src, $index, $default);
    }

    public function fetch_from_array(&$array, $index = null, $default = null) {
        if (is_null($index)) {
            return $array;
        } elseif (isset($array[$index])) {
            return $array[$index];
        } elseif (strpos($index, '/')) {
            $keys = explode('/', $index);

            switch (count($keys)) {
                case 1:
                if (isset($array[$keys[0]])) {
                    return $array[$keys[0]];
                }
                break;

                case 2:
                if (isset($array[$keys[0]][$keys[1]])) {
                    return $array[$keys[0]][$keys[1]];
                }
                break;

                case 3:
                if (isset($array[$keys[0]][$keys[1]][$keys[2]])) {
                    return $array[$keys[0]][$keys[1]][$keys[2]];
                }
                break;

                case 4:
                if (isset($array[$keys[0]][$keys[1]][$keys[2]][$keys[3]])) {
                    return $array[$keys[0]][$keys[1]][$keys[2]][$keys[3]];
                }
                break;
            }
        }

        return $default;
    }

    protected function flattenGroup(&$putInArray, $group, $prefix = '') {
        foreach ($group as $key => $item) {
            // Check first item in item array to see if it is also an array
            if (is_array(reset($item))) {
                self::flattenGroup($putInArray, $item, $prefix . '/' . $key);
            } else {
                $item[1] = $prefix . '/' . $item[1];
                array_push($putInArray, $item);
            }
        }
    }

    public static function handleError($errNo, $errStr, $errFile, $errLine) {
        $return = new \StdClass();
        $return->error = 'Internal Server Error';
        $return->devmessage = [
            'message' => $errStr,
            'location' => $errFile,
            'line' => $errLine
        ];
        self::displayOrLogError($return, 500);
    }

    public static function handleFatalError() {
        $last_error = error_get_last();
        if ($last_error && $last_error['type'] === E_ERROR) {
            // fatal error
            self::handleError(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
        }
    }

    public static function handleException(\Throwable $e) {
        $return = new \StdClass();
        if ($e instanceof AppApiException) {
            foreach ($e->getAdditionals() as $key => $value) {
                $return->{$key} = $value;
            }
        }

        $return->error = $e->getMessage();

        $return->devmessage = [
            'class' => get_class($e),
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'location' => $e->getFile(),
            'line' => $e->getLine()
        ];

        $responseCode = 404;
        if ($e->getCode()) {
            if (is_integer($e->getCode())) {
                $responseCode = $e->getCode();
            } else {
                $responseCode = 500;
            }
        }

        self::displayOrLogError($return, $responseCode);
    }

    public static function displayOrLogError($error, $status = 500) {
        if (wire('config')->debug !== true) {
            $message = 'An Error occurred.';
            if ($error instanceof \Throwable) {
                $message = $error->getMessage();
            } elseif (is_object($error) && isset($error->devmessage)) {
                $message = implode(', ', array_map(
                    function ($v, $k) {
                        return sprintf("%s='%s'", $k, $v);
                    },
                    $error->devmessage,
                    array_keys($error->devmessage)
                ));
            } elseif (is_object($error) && isset($error->message)) {
                $message = $error->message;
            } elseif (is_string($error)) {
                $message = $error;
            }
            wire('log')->save(AppApi::logExceptions, $message);
        }
        self::displayError($error, $status);
    }

    public static function displayError($error, $status = 500) {
        if (is_string($error)) {
            $return = new \StdClass();
            $return->error = (string) $error;
            AppApi::sendResponse($status, $return);
        }

        if (isset($error->devmessage) && !(wire('user')->isSuperuser() || wire('config')->debug === true)) {
            unset($error->devmessage);
        }

        AppApi::sendResponse($status, $error);
    }
}
