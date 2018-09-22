<?php namespace ProcessWire;

/**
 * Router.php
 * 
 * Stuff taken from https://gist.github.com/clsource/dc7be74afcbfc5fe752c
 * and Example Code from @lostkobrakai
 * and some stuff I put in there by myself
 */

// $routesPath = "{$this->config->paths->site}api/Routes.php";

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/RestApiHelper.php";
require_once __DIR__ . "/DefaultRoutes.php";
require_once __DIR__ . "/Auth.php";

use \Firebase\JWT\JWT;

class Router
{ 
  public static function go()
  {
    set_error_handler("ProcessWire\Router::handleError");
    set_exception_handler('ProcessWire\Router::handleException');
    register_shutdown_function('ProcessWire\Router::handleFatalError');

    header("Content-Type: application/json");

    // $routes are coming from this file:
    require_once wire('config')->paths->site . "api/Routes.php";

    $flatUserRoutes = [];
    self::flattenGroup($flatUserRoutes, $routes);

    $flatDefaultRoutes = [];
    self::flattenGroup($flatDefaultRoutes, DefaultRoutes::get());

    $allRoutes = array_merge($flatUserRoutes, $flatDefaultRoutes);

    // create FastRoute Dispatcher:
    $router = function(\FastRoute\RouteCollector $r) use ($allRoutes) {
      foreach($allRoutes as $key => $route) {
        $method = $route[0];
        $url = $route[1];

        // add trailing slash if not present:
        if(substr($url, -1) !== '/') $url .= '/';

        $class = $route[2];
        $function = $route[3];
        $routeParams = isset($route[4]) ? $route[4] : [];

        $r->addRoute($method, $url, [$class, $function, $routeParams]);
      }
    };

    $dispatcher = \FastRoute\simpleDispatcher($router);

    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $url = wire('sanitizer')->url(wire('input')->url);
    
    // strip /api from request url:
    $endpoint = wire('modules')->RestApi->endpoint;
    $regex = '/\/'.$endpoint.'\/?/';
    $url = preg_replace($regex, '/', $url);

    // add trailing slash if not present:
    if(substr($url, -1) !== '/') $url .= '/';

    $routeInfo = $dispatcher->dispatch($httpMethod, $url);

    switch ($routeInfo[0]) {
      case \FastRoute\Dispatcher::NOT_FOUND:
      case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(404);
        return;

      case \FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        $class = $handler[0];
        $method = $handler[1];
        $routeParams = $handler[2];

        return Router::handle($class, $method, $vars, $routeParams);
    }
  }


  public static function handle($class, $method, $vars, $routeParams)
  {
    $return = new \StdClass();
    $vars = (object) $vars;

    $authActive = wire('modules')->RestApi->useJwtAuth == true;
    $routeNeedsAuth = true; // By default every route needs auth if not specified otherwise

    // check if particular route does not need auth
    if(isset($routeParams['auth'])) {
      if($routeParams['auth'] === false)
      $routeNeedsAuth = false;
    }

    // if auth is active (in module settings) and this particular route needs auth (default)
    if($authActive && $routeNeedsAuth)
    {
      try {
        // check for auth header
        $authHeader = self::getAuthorizationHeader();
        if(!$authHeader) {
          self::displayError('No Authorization Header found', 400);
        };

        // Check if jwtSecret is in config
        if(!isset(wire('modules')->RestApi->jwtSecret)) {
          self::displayOrLogError('No JWT secret defined. Please adjust settings in Module RestApi');
        }

        $secret = wire('modules')->RestApi->jwtSecret;
        $token = str_replace('Bearer', '', $authHeader);
        $token = trim($token);
        $decoded = JWT::decode($token, wire('modules')->RestApi->jwtSecret, array('HS256'));
      }
      catch (\Throwable $e)
      {
        throw new \Exception($e->getMessage());
      }
    }

    // If the code runs until here, the request is authenticated 
    // or the request does not need authentication
    // Get Data:
    try {
      // merge url $vars with params
      $vars = array_merge((array) Router::params(), (array) $vars);

      // merge in user id if present in JWT payload otherwise use ProcessWire $user (guest or logged in user)
      $userId = wire('user')->id;
      if(isset($decoded->userId)) $userId = $decoded->userId;
      // merge with $vars
      $vars = array_merge($vars, ['userId' => $userId]);

      // convert array to object:
      $vars = json_decode(json_encode($vars));
      
      $data = $class::$method($vars);

      if(gettype($data) == "string") $return->message = $data;
      else $return = $data;
    } 
    catch (\Throwable $e) {
      $responseCode = 404;
      $return->error = $e->getMessage();
      \ProcessWire\wire('log')->error($e->getMessage());

      if($e->getCode()) $responseCode = $e->getCode();
      http_response_code($responseCode);
    }
  
    echo json_encode($return);
  }


  public static function params($index=null, $default = null, $source = null) 
  {
    // check for php://input and merge with $_REQUEST
      if ((isset($_SERVER["CONTENT_TYPE"]) &&
        stripos($_SERVER["CONTENT_TYPE"],'application/json') !== false) ||
        (isset($_SERVER["HTTP_CONTENT_TYPE"]) &&
        stripos($_SERVER["HTTP_CONTENT_TYPE"],'application/json') !== false) // PHP build in Webserver !?
        ) {
        if ($json = json_decode(@file_get_contents('php://input'), true)) {
          $_REQUEST = array_merge($_REQUEST, $json);
        }
      }

      $src = $source ? $source : $_REQUEST;

      //Basic HTTP Authetication
      // if (isset($_SERVER['PHP_AUTH_USER'])) {
      // $credentials = [
      // "uname" => $_SERVER['PHP_AUTH_USER'],
      // "upass" => $_SERVER['PHP_AUTH_PW']
      // ];
      // $src = array_merge($src, $credentials);
      // }

      return Router::fetch_from_array($src, $index, $default);
  }


  public static function fetch_from_array(&$array, $index=null, $default = null) 
  {
    if (is_null($index)) 
    {
      return $array;
    } 
    elseif (isset($array[$index])) 
    {
      return $array[$index];
    } 
    elseif (strpos($index, '/')) 
    {
      $keys = explode('/', $index);

      switch(count($keys))
      {
        case 1:
          if (isset($array[$keys[0]])){
            return $array[$keys[0]];
          }
          break;

        case 2:
          if (isset($array[$keys[0]][$keys[1]])){
            return $array[$keys[0]][$keys[1]];
          }
          break;

        case 3:
          if (isset($array[$keys[0]][$keys[1]][$keys[2]])){
            return $array[$keys[0]][$keys[1]][$keys[2]];
          }
          break;

        case 4:
          if (isset($array[$keys[0]][$keys[1]][$keys[2]][$keys[3]])){
            return $array[$keys[0]][$keys[1]][$keys[2]][$keys[3]];
          }
          break;
      }
    }

    return $default;
  }

  private static function getAuthorizationHeader() {
    // convert all headers to lowercase:
    $headers = array();
    foreach($_SERVER as $key => $value) {
      $headers[strtolower($key)] = $value;
    }

    if(array_key_exists('authorization', $headers)) return $headers['authorization'];
    if(array_key_exists('http_authorization', $headers)) return $headers['http_authorization'];
    
    return null;
  }

  private static function flattenGroup (&$putInArray, $group, $prefix = '') {
    foreach($group as $key => $item) {
      if(is_array($item[0])) {
        self::flattenGroup($putInArray, $item, '/' . $key);
      } else {
        $item[1] = $prefix . '/' . $item[1];
        array_push($putInArray, $item);
      }
    }
  }

  public static function handleError($errNo, $errStr, $errFile, $errLine) {
    // throw new \Exception('handleError Exception');
    // throw new \ErrorException($errStr, 0, $errNo, $errFile, $errLine);
    // exit();

    $message = "Error: $errStr. File: $errFile:$errLine";
    self::displayOrLogError($message);
  }

  public static function handleException(\Throwable $e) {
    echo "handle exception";

    $message = $e->getMessage();
    self::displayOrLogError($message);
  }

  public static function handleFatalError() {
    $last_error = error_get_last();
    if ($last_error['type'] === E_ERROR) {
      // fatal error
      self::handleError(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
    }
  }

  public static function displayOrLogError ($message, $status = 500) {
    if(wire('config')->debug !== true) {
      wire('log')->save('api-error', $message);
      self::displayError('Error: If you are a system administrator, please check logs', $status);
    }
    else self::displayError($message, $status);
  }

  public static function displayError ($message, $status = 500) {
    http_response_code($status);
    $return = new \StdClass();
    $return->error = $message;
    echo json_encode($return);
    exit();
  }
}

// set_error_handler("ProcessWire\Router::handleError");
// set_exception_handler('ProcessWire\Router::handleException');
// register_shutdown_function('ProcessWire\Router::handleFatalError');