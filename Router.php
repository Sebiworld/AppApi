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
require_once __DIR__ . "/Auth.php";

use \Firebase\JWT\JWT;

// set custom error handler:
// set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
//   $message = "Error: $errstr. File: $errfile:$errline";

//   \TD::fireLog('EXCEPTION: ' . $message);

//   // if(wire('config')->debug === true) throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
//   // else {
//   //   wire('log')->save('api-error', $message);
//   //   throw new \Exception('Error. If you are a system administrator, please check logs', 500);
//   // }
// });

// set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
//   echo "error";
//   die();
// });

// class ErrorHandling {

//   public static function error($errNo, $errStr, $errFile, $errLine) {
//     echo "error";
//     exit();
//   }
// }

// function exceptionHandler($exception) {
//   // Show a human message to the user.
//   echo $exception->getMessage();
// 	echo '<h1>Server error (500)</h1>';
// 	echo '<p>Please contact your administrator, etc.</p>';
// }

// set_exception_handler('ProcessWire\exceptionHandler');
// throw new \Exception();
// echo "hallo";
// exit();


class Router
{
  /**
   * @param callable $callback Route configurator
   * @param string   $path Optionally overwrite the default of using the whole urlSegmentStr
   * @throws Wire404Exception
   */
  // public static function go(callable $callback)
  public static function go()
  {
    set_error_handler("ProcessWire\Router::handleError");
    set_exception_handler('ProcessWire\Router::handleException');
    register_shutdown_function('ProcessWire\Router::handleFatalError');

    // $routes are coming from this file:
    require_once wire('config')->paths->site . "api/Routes.php";

    $dispatcher = \FastRoute\simpleDispatcher($routes);

    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $url = wire('sanitizer')->url(wire('input')->url);

    // strip /api from request url:
    $regex = '/\/api\/?/';
    $url = preg_replace($regex, '/', $url);

    $routeInfo = $dispatcher->dispatch($httpMethod, $url);

    switch ($routeInfo[0]) {
      case \FastRoute\Dispatcher::NOT_FOUND:
      case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        // \TD::fireLog('404');
        // throw new Wire404Exception();
        // throw new Wire404Exception('error');
        // throw new \Exception('das ist eine exception');
        http_response_code(404);
        return;

      case \FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        list($class, $method) = explode('@', $handler, 2);
        return Router::handle($class, $method, $vars);
    }
  }


  public static function handle($class, $method, $vars)
  {
    $authActive = true;
    $return = new \StdClass();
    $vars = (object) $vars;

    // if regular and not auth request, check Authorization:
    // otherwise go right through regular api handling
    if($authActive === true && $class !== Auth::class)
    {
      try {
        // convert all headers to lowercase:
        $headers = array();
        foreach($_SERVER as $key => $value) {
          $headers[strtolower($key)] = $value;
        }

        // check for auth header
        // if(!array_key_exists('authorization', $headers)) {
        //   http_response_code(400);
        //   $return->error = 'No Authorization Header found';
        //   echo json_encode($return);
        //   return;
        // };

        // Check if jwtSecret is in config
        if(!isset(wire('modules')->RestApi->jwtSecretzz)) {
          throw new \Exception('no JWT scret defined');
        }

        echo 'jo klappt';

        // $secret = wire('config')->jwtSecret;
        // list($jwt) = sscanf($headers['authorization'], 'Bearer %s');
        // $decoded = JWT::decode($jwt, $secret, array('HS256'));
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
      if (isset($_SERVER['PHP_AUTH_USER'])) {
      $credentials = [
      "uname" => $_SERVER['PHP_AUTH_USER'],
      "upass" => $_SERVER['PHP_AUTH_PW']
      ];
      $src = array_merge($src, $credentials);
      }

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

  public static function handleError($errNo, $errStr, $errFile, $errLine) {
    // if(wire('config')->debug === true) throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    // echo "handleError";
    // throw new \Exception('handleError Exception');
    throw new \ErrorException($errStr, 0, $errNo, $errFile, $errLine);

    // $message = "Error: $errStr. File: $errFile:$errLine";
    // self::displayOrLogError($message);
  }

  public static function handleException(\Throwable $e) {
    // echo "handle exception";

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

  public static function displayOrLogError ($message) {
    http_response_code(500);
    $return = new \StdClass();
    $return->error = $message;
    
    if(wire('config')->debug !== true) {
      wire('log')->save('api-error', $message);
      $return->error = 'Error: If you are a system administrator, please check logs';
    }

    echo json_encode($return);
    exit();
  }
}

// set_error_handler("ProcessWire\Router::handleError");
// set_exception_handler('ProcessWire\Router::handleException');
// register_shutdown_function('ProcessWire\Router::handleFatalError');