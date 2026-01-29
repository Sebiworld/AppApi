<?php
namespace ProcessWire;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/AppApiHelper.php';
require_once __DIR__ . '/DefaultRoutes.php';
require_once __DIR__ . '/Auth.php';

class Router extends WireData {
	const methodsOrder = ['OPTIONS', 'GET', 'POST', 'UPDATE', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'CONNECT', 'TRACE'];
	// Tracks the buffer nesting level so we can discard stray output safely.
	private static $baseOutputBufferLevel = null;

	public function ___setCorsHeaders() {
		$disableAutoHeaders = !!@wire('modules')->getConfig('AppApi', 'disable_automatic_access_control_headers');

		if (isset($_SERVER['HTTP_ORIGIN']) && !$disableAutoHeaders) {
			header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
			header('Access-Control-Allow-Headers: Content-Type, AUTHORIZATION, X-API-KEY');
			header('Access-Control-Allow-Credentials: true');
		}
	}

	private static function pathFromPwRoot($fullPath) {
		$rootPath = wire('config')->paths->root;
		if (substr($fullPath, 0, strlen($rootPath)) === $rootPath) {
			return '/' . substr($fullPath, strlen($rootPath));
		}
		return $fullPath;
	}

	/**
	 * Merges all registered routes to a flat, duplicate-free routes-array that can be used by FastRoute.
	 *
	 * Flat route definition:
	 * [0] method
	 * [1] url
	 * [2] handler-class
	 * [3] function
	 * [4] settings data
	 * [5] documentation data
	 * [6] trace data
	 *
	 * @param array $registeredRoutes External endpoint-routes that are registered via AppApi->registerRoute()
	 * @param boolean $includeTrace If true, the output array will include trace data at index 6
	 *
	 * @return array
	 */
	private function getRoutesWithoutDuplicatesFlat($registeredRoutes, $includeTrace = false) {
		// $routes are coming from this file:
		$routesPathRelative = $this->wire('modules')->AppApi->routes_path;
		$routesPath = wire('config')->paths->site . 'api/Routes.php';
		if (is_string($routesPathRelative) && !empty($routesPathRelative) && substr($routesPathRelative, -1) !== '/') {
			$routesPath = wire('config')->paths->root . $routesPathRelative;
		}
		require_once $routesPath;

		$flatDefaultRoutes = [];
		if ($includeTrace) {
			self::flattenGroup($flatDefaultRoutes, DefaultRoutes::get(), '', [
				'file' => wire('config')->urls->AppApi . 'classes/DefaultRoutes.php'
			]);
		} else {
			self::flattenGroup($flatDefaultRoutes, DefaultRoutes::get());
		}

		$flatRegisteredRoutes = [];
		if (is_array($registeredRoutes) && !empty($registeredRoutes)) {
			foreach ($registeredRoutes as $key => $route) {
				if (!isset($route['routeDefinition'])) {
					continue;
				}
				$def = [];
				$def[$key] = $route['routeDefinition'];

				if ($includeTrace) {
					self::flattenGroup($flatRegisteredRoutes, $def, '', $route['trace'] ?? []);
				} else {
					self::flattenGroup($flatRegisteredRoutes, $def);
				}
			}
		}

		$flatUserRoutes = [];
		if ($includeTrace) {
			self::flattenGroup($flatUserRoutes, $routes, '', [
				'file' => self::pathFromPwRoot($routesPath)
			]);
		} else {
			self::flattenGroup($flatUserRoutes, $routes);
		}

		// Registered Routes can overwrite default routes, user-defined routes in Routes.php can overwrite external routes:
		$allRoutes = array_merge($flatDefaultRoutes, $flatRegisteredRoutes, $flatUserRoutes);

		$routesWithoutDuplicates = [];
		foreach ($allRoutes as $item) {
			if (!isset($item[1]) || !isset($item[0])) {
				continue;
			}
			$routesWithoutDuplicates[$item[1] . '#' . $item[0]] = $item;
		}

		return array_values($routesWithoutDuplicates);
	}

	public function getRoutesWithoutDuplicates($registeredRoutes, $includeTrace = false) {
		$routesWithoutDuplicates = $this->getRoutesWithoutDuplicatesFlat($registeredRoutes, $includeTrace);
		$groupedRoutes = [];

		foreach ($routesWithoutDuplicates as $key => $route) {
			if (!is_array($route)) {
				continue;
			}
			$route[1] = '/' . trim($route[1], '/');
			if (!isset($groupedRoutes[$route[1]]) || !is_array($groupedRoutes[$route[1]])) {
				$groupedRoutes[$route[1]] = [];
			}
			$groupedRoutes[$route[1]][] = $route;
		}

		foreach ($groupedRoutes as $key => $children) {
			if (!is_array($groupedRoutes[$key])) {
				continue;
			}

			usort($groupedRoutes[$key], function ($a, $b) {
				$sortKeyA = array_search($a[0], SELF::methodsOrder);
				$sortKeyB = array_search($b[0], SELF::methodsOrder);

				if ($sortKeyA === false && $sortKeyB === false) {
					return 0;
				} else if ($sortKeyA === false) {
					return -1;
				} else if ($sortKeyB === false) {
					return 1;
				}

				return $sortKeyA > $sortKeyB ? 1 : -1;
			});
		}
		ksort($groupedRoutes);

		return $groupedRoutes;
	}

	public function ___go($registeredRoutes) {
		$this->registerErrorHandlers();
		self::startOutputBuffer();
		$this->setCorsHeaders();

		try {
			$routesWithoutDuplicates = $this->getRoutesWithoutDuplicatesFlat($registeredRoutes);

			// create FastRoute Dispatcher:
			$router = function (\FastRoute\RouteCollector $r) use ($routesWithoutDuplicates) {
				foreach ($routesWithoutDuplicates as $key => $route) {
					if (!is_array($route)) {
						continue;
					}
					$method = $route[0];
					$url = $route[1];

					// add trailing slash if not present:
					$lastChar = substr($url, -1);
					if ($lastChar !== '/'&& $lastChar !== ']') {
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

			$routeInfo = $dispatcher->dispatch($httpMethod, SELF::getCurrentUrl());

			// Routeinfo and Auth extracted. Router::handle will return the info that should be output
			$return = Router::handle($routeInfo);

			$responseCode = 200;
			if (is_array($return) && isset($return['responseCode']) && is_numeric($return['responseCode'])) {
				$responseCode = $return['responseCode'];
				unset($return['responseCode']);
			} elseif (is_object($return) && isset($return->responseCode) && is_numeric($return->responseCode)) {
				$responseCode = $return->responseCode;
				unset($return->responseCode);
			}

			self::clearOutputBuffer();
			AppApi::sendResponse($responseCode, $return);
		} catch (\Throwable $e) {
			// Show Exception as json-response and exit.
			self::handleException($e);
		}
	}

	/**
	 * Returns the current url (will be used by fastroute's dispatcher)
	 * @return string url
	 */
	protected static function getCurrentUrl() {
		$url = AppApi::getRelativeRequestUrl();

		//strip query parameters from url
		$url = preg_replace('/[?].+$/i', '', $url);

		// strip /api from request url:
		$endpoint = wire('modules')->AppApi->endpoint;

		// support / in endpoint url:
		$endpoint = str_replace('/', "\/", $endpoint);

		$regex = '/\/' . $endpoint . '\/?/';
		$url = preg_replace($regex, '/', $url);

		// add trailing slash if not present:
		if (substr($url, -1) !== '/') {
			$url .= '/';
		}

		return $url;
	}

	public function ___handle($routeInfo) {
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
			throw new AppApiException('Apikey not valid.', 401, ['errorcode' => 'invalid_apikey']);
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
			throw new AppApiException('Route not allowed for this application', 400, ['errorcode' => 'route_not_allowed_for_application']);
		}

		if (!empty($routeParams['applications']) && is_array($routeParams['applications']) && !in_array(Auth::getInstance()->getApplication()->getID(), $routeParams['applications'])) {
			throw new AppApiException('Route not allowed for this application', 400, ['errorcode' => 'route_not_allowed_for_application']);
		}

		// Check if particular route does need auth:
		if (isset($routeParams['auth']) && $routeParams['auth'] === true && !$this->wire('user')->isLoggedIn()) {
			throw new AppApiException('User does not have authorization', 401, ['errorcode' => 'user_not_authorized']);
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
				throw new AppApiException(
					'User does not have one of the required roles for this route.',
					403,
					['errorcode' => 'user_missing_required_role']
			);
			}
		}

		// If the code runs until here, the request is authenticated
		// or the request does not need authentication

		// merge url $vars with params
		$vars = array_merge((array) Router::params(), (array) $vars);
		// $vars['auth'] = Auth::getInstance();

		// convert array to object:
		$vars = json_decode(json_encode($vars));

		$data = $class::$methodName($vars);

		if (@$this->wire('modules')->getConfig('AppApi', 'access_logging')) {
			$logdata = [];
			$logdata[] = Auth::getInstance()->getApplicationLog();
			$logdata[] = Auth::getInstance()->getApikeyLog();
			if (Auth::getInstance()->getTokenLog()) {
				$logdata[] = Auth::getInstance()->getTokenLog();
			}

			$url = $this->wire('modules')->AppApi->endpoint;
			if (empty($url)) {
				$url = '/api';
			} else {
				$url = '/' . trim($url, '/');
			}
			$url .= SELF::getCurrentUrl();

			wire('log')->save(
				AppApi::logAccess,
				'Successful request with: ' . implode(', ', $logdata),
				[
					'url' => $url
				]
			);
		}


		return $data;
	}

	public function ___params($index = null, $default = null, $source = null) {
		// check for php://input and merge with $_REQUEST
		if (
			(isset($_SERVER['CONTENT_TYPE']) &&
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

	public function ___fetch_from_array(&$array, $index = null, $default = null) {
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

	protected static function flattenGroup(&$putInArray, $group, $prefix = '', $traceData = []) {
		foreach ($group as $key => $item) {
			// Check first item in item array to see if it is also an array
			if (is_array(reset($item))) {
				self::flattenGroup($putInArray, $item, $prefix . '/' . $key, $traceData);
			} else if (isset($item[1])) {
				$item[1] = $prefix . '/' . $item[1];

				if (!empty($traceData)) {
					if (!isset($item[2])) {
						$item[2] = '';
					}
					if (!isset($item[3])) {
						$item[3] = '';
					}
					if (!isset($item[4])) {
						$item[4] = [];
					}
					if (!isset($item[5])) {
						$item[5] = [];
					}

					$item[6] = $traceData;
					if (isset($item[6]['file'])) {
						$item[6]['file'] = self::pathFromPwRoot($item[6]['file']);
					}
				}

				array_push($putInArray, $item);
			}
		}
	}

	public function ___registerErrorHandlers() {
		set_error_handler("ProcessWire\Router::handleError");
		set_exception_handler('ProcessWire\Router::handleException');
		register_shutdown_function('ProcessWire\Router::handleFatalError');
	}

	public static function handleError($errNo, $errStr, $errFile, $errLine) {
		if (error_reporting()) {
			$return = new \StdClass();
			$return->error = 'Internal Server Error';
			$return->error_reporting = error_reporting();
			$return->devmessage = [
				'message' => $errStr,
				'location' => $errFile,
				'line' => $errLine
			];
			self::logError($return, 500);
		}

		// Return true to prevent PHP from also rendering the warning/error to the client.
		return true;
	}

	public static function handleFatalError() {
		self::clearOutputBuffer();
		$last_error = error_get_last();
		if ($last_error && $last_error['type'] === E_ERROR) {
			// fatal error
			self::handleError(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
		}
	}

	public static function handleException(\Throwable $e) {
		self::clearOutputBuffer();
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

		// Do not log non-error exceptions (e.g. 204)
		if (!is_numeric($responseCode) || $responseCode >= 400) {
			self::logError($return, $responseCode);
		}

		self::displayError($return, $responseCode);
	}

	public static function logError($error, $status = 500) {
		if (isset(wire('config')->appApiLogErrors) && wire('config')->appApiLogErrors === false) {
			return;
		}

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

		$url = wire('modules')->AppApi->endpoint;
		if (empty($url)) {
			$url = '/api';
		} else {
			$url = '/' . trim($url, '/');
		}
		$url .= SELF::getCurrentUrl();

		wire('log')->save(
			AppApi::logExceptions,
			$message,
			[
				'url' => $url
			]
		);
	}

	public static function displayError($error, $status = 500) {
		if (is_string($error)) {
			$return = new \StdClass();
			$return->error = (string) $error;
			self::clearOutputBuffer();
			AppApi::sendResponse($status, $return);
		}

		if (isset($error->devmessage) && !(wire('user')->isSuperuser() || wire('config')->debug === true || wire('config')->appApiEnableDevmessages === true)) {
			unset($error->devmessage);
		}

		self::clearOutputBuffer();
		AppApi::sendResponse($status, $error);
	}

	/**
	 * Start buffering output so any warnings from third-party code can be dropped before sending JSON.
	 **/
	private static function startOutputBuffer() {
		if (self::$baseOutputBufferLevel === null) {
			self::$baseOutputBufferLevel = ob_get_level();
		}

		ob_start();
	}

	/**
	 * Bootstrap entry point to suppress error display and install handlers early for API calls.
	 **/
	public static function bootstrapForApiRequest() {
		@ini_set('display_errors', '0');
		@ini_set('display_startup_errors', '0');
		$router = new self();
		$router->registerErrorHandlers();

		self::startOutputBuffer();
	}

	/**
	 * Clear any output buffers above the base level to discard stray output.
	 **/
	private static function clearOutputBuffer() {
		if (self::$baseOutputBufferLevel === null) {
			return;
		}

		while (ob_get_level() > self::$baseOutputBufferLevel) {
			ob_end_clean();
		}
	}
}
