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

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/RestApiHelper.php";
require_once __DIR__ . "/DefaultRoutes.php";
require_once __DIR__ . "/Auth.php";

use \Firebase\JWT\JWT;

class Router {
	public function go() {
		set_error_handler("ProcessWire\Router::handleError");
		set_exception_handler('ProcessWire\Router::handleException');
		register_shutdown_function('ProcessWire\Router::handleFatalError');

		if (isset($_SERVER['HTTP_ORIGIN'])) {
			header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
			header("Access-Control-Allow-Headers: Content-Type, Authorization");
			header('Access-Control-Allow-Credentials: true');
			header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,HEAD,OPTIONS");
		}

		try{
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

	    	// support / in endpoint url:
			$endpoint = str_replace("/", "\/", $endpoint);

			$regex = '/\/'.$endpoint.'\/?/';
			$url = preg_replace($regex, '/', $url);

	    	// add trailing slash if not present:
			if(substr($url, -1) !== '/') $url .= '/';

			$routeInfo = $dispatcher->dispatch($httpMethod, $url);

			return Router::handle($class, $method, $vars, $routeParams);
		} catch (\Throwable $e) {
			// Show Exception as json-response and exit.
			$return = new \StdClass();
			$return->error = $e->getMessage();

			$responseCode = 404;
			if($e->getCode()) $responseCode = $e->getCode();

			if(wire('config')->debug !== true) {
				wire('log')->save('api-exception', $responseCode . ': ' . $e->getMessage());
			}

			self::sendResponse($responseCode, $return);
		}
	}


	public function handle($routeInfo) {
		$return = new \StdClass();

		if(!isset($routeInfo[0]) || $routeInfo[0] !== \FastRoute\Dispatcher::FOUND){
			// Handle FastRoute-Errors:
			switch ($routeInfo[0]) {
				case \FastRoute\Dispatcher::NOT_FOUND:
					throw new \Exception('Route not found', 404);
				case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
					throw new \Exception('Method not allowed', 405);
			}
		}

		$handler = $routeInfo[1];
		$vars = (object) $routeInfo[2];

		$class = $handler[0];
		$method = $handler[1];
		$routeParams = $handler[2];

		$authMethod = wire('modules')->RestApi->authMethod;

		// If AuthMethod JWT is allowed, check if a valid token is available and log the user in:
		if($authMethod === 'jwt' && Auth::isValidTokenPresent()){
			// A valid token was found, user-session restored
		}

    	$routeNeedsAuth = true; // By default every route needs auth if not specified otherwise

    	// check if particular route does not need auth
    	if(isset($routeParams['auth'])) {
    		if($routeParams['auth'] === false){
    			$routeNeedsAuth = false;
    		}
    	}

    	// If 'jwt'-auth is active (in the module settings): Check for Bearer-token and log the user in.
    	if($authMethod === 'jwt') {

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
    		} catch (\Throwable $e) {
    			throw new \Exception($e->getMessage());
    		}
    	}

    	if($authMethod === 'session' && $routeNeedsAuth) {
    		if(wire('user')->isGuest()) self::displayError('user does not have authorization', 401);
    	}

	    // If the code runs until here, the request is authenticated
	    // or the request does not need authentication
	    // Get Data:

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

		if(gettype($data) == "string"){
			$return->message = $data;
		}else{
			$return = $data;
		}

		return $return;
    }

    public function params($index=null, $default = null, $source = null) {
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


    public function fetch_from_array(&$array, $index=null, $default = null) {
    	if (is_null($index)) {
    		return $array;
    	}else if (isset($array[$index])) {
    		return $array[$index];
    	} else if (strpos($index, '/')) {
    		$keys = explode('/', $index);

    		switch(count($keys)) {
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

    protected function flattenGroup (&$putInArray, $group, $prefix = '') {
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
    	$message = "Error: $errStr. File: $errFile:$errLine";
    	self::displayOrLogError($message);
    }

    public static function handleException(\Throwable $e) {
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

    public static  function displayOrLogError ($message, $status = 500) {
    	if(wire('config')->debug !== true) {
    		wire('log')->save('api-error', $message);
    		self::displayError('Error: If you are a system administrator, please check logs', $status);
    	}
    	else self::displayError($message, $status);
    }

    public static function displayError ($message, $status = 500) {
    	if(error_reporting() === 0) return;

    	$return = new \StdClass();
    	$return->error = $message;

    	self::sendResponse($status, $return);
    }

    /**
     * Helper method to get a string description for an HTTP status code. Is used if server doesn't support http_response_code().
     * See https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     * @param integer 	$status  	http-statuscode
     * @return string 	standardized message for the requested statuscode
     */
    private static function getStatusCodeMessage($status){
    	$codes = Array(
    		100 => 'Continue',
    		101 => 'Switching Protocols',
    		200 => 'OK',
    		201 => 'Created',
    		202 => 'Accepted',
    		203 => 'Non-Authoritative Information',
    		204 => 'No Content',
    		205 => 'Reset Content',
    		206 => 'Partial Content',
    		207 => 'Multi-Status',
    		208 => 'Already Reported',
    		226 => 'IM Used',
    		300 => 'Multiple Choices',
    		301 => 'Moved Permanently',
    		302 => 'Found',
    		303 => 'See Other',
    		304 => 'Not Modified',
    		305 => 'Use Proxy',
    		306 => '(Unused)',
    		307 => 'Temporary Redirect',
    		308 => 'Permanent Redirect',
    		400 => 'Bad Request',
    		401 => 'Unauthorized',
    		402 => 'Payment Required',
    		403 => 'Forbidden',
    		404 => 'Not Found',
    		405 => 'Method Not Allowed',
    		406 => 'Not Acceptable',
    		407 => 'Proxy Authentication Required',
    		408 => 'Request Timeout',
    		409 => 'Conflict',
    		410 => 'Gone',
    		411 => 'Length Required',
    		412 => 'Precondition Failed',
    		413 => 'Request Entity Too Large',
    		414 => 'Request-URI Too Long',
    		415 => 'Unsupported Media Type',
    		416 => 'Requested Range Not Satisfiable',
    		417 => 'Expectation Failed',
    		418 => 'I\'m a teapot',
    		421 => 'Misdirected Request',
    		422 => 'Unprocessable Entity',
    		423 => 'Locked',
    		424 => 'Failed Dependency',
    		426 => 'Upgrade Required',
    		428 => 'Precondition Required',
    		429 => 'Too Many Requests',
    		431 => 'Request Header Fields Too Large',
    		451 => 'Unavailable For Legal Reasons',
    		500 => 'Internal Server Error',
    		501 => 'Not Implemented',
    		502 => 'Bad Gateway',
    		503 => 'Service Unavailable',
    		504 => 'Gateway Timeout',
    		505 => 'HTTP Version Not Supported',
    		506 => 'Variant Also Negotiates',
    		507 => 'Insufficient Storage',
    		508 => 'Loop Detected',
    		510 => 'Not Extended',
    		511 => 'Network Authentication Required'
    		);

    	return (isset($codes[$status])) ? $codes[$status] : '';
    }

    /**
     * Helper method to send a HTTP response code/message. Transforms a $body-array (or object) to json.
     * @param  integer $status       	statuscode for the server-response (See https://en.wikipedia.org/wiki/List_of_HTTP_status_codes). (Default: 200)
     * @param  string  $body         	body for the response. If body is an array or object and $content_type is 'application/json', the body will be json_encoded automatically.
     * @param  string  $content_type 	The content-type of the response. Default value is 'text/html'. If an array or object is given as $body, 'application/json' will be default.
     */
    public static function sendResponse($status = 200, $body = '', $content_type = false){

    	// Set status header:
    	$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
    	$statusMessage = '';
    	if(function_exists('http_response_code')){
    		$statusMessage = http_response_code($status);
    	}else{
    		// Fallback to custom method if http_response_code is not supported
    		$statusMessage = self::getStatusCodeMessage($status);
    	}
    	$status_header = $protocol . ' ' . $status . ' ' . $statusMessage;
    	header($status_header);

    	// Set content-type header, if its not explicitly set:
    	if(!$content_type){
	    	$content_type = 'text/html';
	    	if(is_array($body) || is_object($body)){
	    		$content_type = 'application/json';
	    	}
	    }
    	header('Content-type: ' . $content_type);

    	// Encode content to json if array or object and content-type is application/json:
    	if($content_type === 'application/json' && (is_array($body) || is_object($body))){
    		$jsonbody = json_encode($body);
    		if($jsonbody !== FALSE){
    			$body = $jsonbody;
    		}
    	}

    	echo $body;
    	exit();
    }
}