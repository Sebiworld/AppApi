<?php
namespace ProcessWire;

require_once __DIR__ . '/classes/Router.php';
require_once __DIR__ . '/classes/Exceptions.php';
require_once __DIR__ . '/classes/Application.php';
require_once __DIR__ . '/classes/Apikey.php';
require_once __DIR__ . '/classes/Apptoken.php';

class AppApi extends Process implements Module {
	const manageApplicationsPermission = 'appapi_manage_applications';
	const tableApplications = 'appapi_applications';
	const tableApikeys = 'appapi_apikeys';
	const tableApptokens = 'appapi_apptokens';

	const logExceptions = 'appapi-exceptions';
	const logAccess = 'appapi-access';

	protected $apiCall = false;

	protected $registeredRoutes = [];

	public static function getModuleInfo() {
		return [
			'title' => 'AppApi',
			'summary' => 'Module to create a REST API with ProcessWire',
			'version' => '1.3.0',
			'author' => 'Sebastian Schendel',
			'icon' => 'terminal',
			'href' => 'https://modules.processwire.com/modules/app-api/',
			'requires' => [
				'PHP>=7.2.0',
				'ProcessWire>=3.0.98'
			],

			'autoload' => true,
			'singular' => true,
			'permissions' => [
				'appapi_manage_applications' => 'Manage AppApi settings'
			],
			'page' => [
				'name' => 'appapi',
				'parent' => 'setup',
				'title' => 'AppApi',
				'icon' => 'terminal'
			],
		];
	}

	public function ___install() {
		parent::___install();

		$apiPath = "{$this->config->paths->site}api";
		$routesPath = "{$this->config->paths->site}api/Routes.php";
		$examplesPath = "{$this->config->paths->site}api/Example.php";

		if (!file_exists($apiPath)) {
			$this->files->mkdir("{$this->config->paths->site}api");
			$this->notices->add(new NoticeMessage("$this->className: Created api directory: $apiPath"));
		}

		if (!file_exists($routesPath)) {
			$this->files->copy(__DIR__ . '/apiTemplate/Routes.php', $routesPath);
			$this->notices->add(new NoticeMessage("$this->className: Created Routes.php in: $routesPath"));
		}

		if (!file_exists($examplesPath)) {
			$this->files->copy(__DIR__ . '/apiTemplate/Example.php', $examplesPath);
			$this->notices->add(new NoticeMessage("$this->className: Created Example.php in: $examplesPath"));
		}

		$this->createDBTables();
	}

	private function createDBTables() {
		$statement = 'CREATE TABLE IF NOT EXISTS `' . self::tableApplications . '` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `created` datetime NOT NULL,
    `created_user_id` int(11) NOT NULL,
    `modified` datetime NOT NULL,
    `modified_user_id` int(11) NOT NULL,
    `title` varchar(100) NOT NULL,
    `description` TEXT,
    `authtype` int(11) NOT NULL,
    `logintype` LONGTEXT NOT NULL,
    `token_secret` varchar(100) NOT NULL,
    `expires_in` int(11) NOT NULL,
    `accesstoken_secret` varchar(100) NOT NULL,
    `default_application` int(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;';

		$statement .= 'CREATE TABLE IF NOT EXISTS `' . self::tableApikeys . '` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `application_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    `created_user_id` int(11) NOT NULL,
    `modified` datetime NOT NULL,
    `modified_user_id` int(11) NOT NULL,
    `key` varchar(100) NOT NULL,
    `version` varchar(100) NOT NULL,
    `description` TEXT,
    `accessable_until` datetime,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;';

		$statement .= 'CREATE TABLE IF NOT EXISTS `' . self::tableApptokens . '` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `application_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    `created_user_id` int(11) NOT NULL,
    `modified` datetime NOT NULL,
    `modified_user_id` int(11) NOT NULL,
    `token_id` varchar(100) NOT NULL,
    `user_id` int(11) NOT NULL,
    `last_used` datetime,
    `expiration_time` datetime,
    `not_before_time` datetime,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;';

		try {
			$database = wire('database');
			$database->exec($statement);
			$this->notices->add(new NoticeMessage('Created db-tables.'));
		} catch (\Exception $e) {
			$this->error('Error creating db-tables: ' . $e->getMessage());
		}
	}

	public function ___uninstall() {
		parent::___uninstall();

		try {
			$deleteStatement = '
      DROP TABLE IF EXISTS `' . self::tableApikeys . '`;
      DROP TABLE IF EXISTS `' . self::tableApptokens . '`;
      DROP TABLE IF EXISTS `' . self::tableApplications . '`;
      ';

			$datenbank = wire('database');
			$datenbank->exec($deleteStatement);

			$this->notices->add(new NoticeMessage('Removed db-tables.'));

			$this->notices->add(new NoticeMessage("$this->className: You need to remove the site/api folder yourself if you're not planning on using it anymore"));
		} catch (\Exception $e) {
			$this->error('Error dropping db-tables: ' . $e->getMessage());
		}
	}

	public function ___upgrade($fromVersion, $toVersion) {
		if (version_compare($fromVersion, '1.0.0', '<')) {
			$this->createDBTables();

			if ($this->authMethod === 'jwt') {
				// Create a new application and copy the jwt-secret
				$application = new Application();
				$application->regenerateTokenSecret();
				$application->regenerateAccesstokenSecret();

				$application->setTokenSecret($this->jwtSecret);
				$application->setTitle('My Rest-Application');
				$application->setDescription('Application was automatically generated with information from an older module-version.');
			}
		} elseif (version_compare($fromVersion, '1.1.0', '<')) {
			// Add default_application column to application
			try {
				$alterStatement = '
        ALTER TABLE `' . self::tableApplications . '` ADD COLUMN `default_application` int(1) NOT NULL DEFAULT 0;
        ';

				$datenbank = wire('database');
				$datenbank->exec($alterStatement);

				$this->notices->add(new NoticeMessage('Successfully Altered Database-Scheme.'));
			} catch (\Exception $e) {
				$this->error('Error altering db-tables: ' . $e->getMessage());
			}
		} elseif (version_compare($fromVersion, '1.1.0', '==') && version_compare($toVersion, '1.1.1', '==')) {
			// Add default_application column to application
			try {
				$alterStatement = '
        ALTER TABLE `' . self::tableApplications . '` MODIFY COLUMN `default_application` int(1) NOT NULL DEFAULT 0;
        ';

				$datenbank = wire('database');
				$datenbank->exec($alterStatement);

				$this->notices->add(new NoticeMessage('Successfully Altered Database-Scheme.'));
			} catch (\Exception $e) {
				$this->error('Error altering db-tables: ' . $e->getMessage());
			}
		} elseif (version_compare($fromVersion, '1.2.7', '<') && version_compare($toVersion, '1.2.6', '>')) {
			// Add default_application column to application
			try {
				$alterStatement = '
        ALTER TABLE `' . self::tableApplications . '` ADD COLUMN `logintype` LONGTEXT NOT NULL;
        ';

				$datenbank = wire('database');
				$datenbank->exec($alterStatement);

				$this->notices->add(new NoticeMessage('Successfully Altered Database-Scheme.'));
			} catch (\Exception $e) {
				$this->error('Error altering db-tables: ' . $e->getMessage());
			}
		}
	}

	public function ___execute() {
		$this->headline('AppApi');

		$this->config->scripts->add(
			$this->config->urls->AppApi . 'assets/AppApi.js'
		);

		return [
			'module' => $this,
			'existingLogs' => $this->wire('log')->getLogs(),
			'accesslogsActivated' => @$this->wire('modules')->getConfig('AppApi', 'access_logging'),
			'configUrl' => $this->wire('config')->urls->admin . 'module/edit?name=AppApi'
		];
	}

	public function ___executeApplications() {
		$this->headline($this->_('AppApi') . ' ' . $this->_('Applications'));

		$this->config->scripts->add(
			$this->config->urls->AppApi . 'assets/AppApi.js'
		);

		try {
			return [
				'applications' => $this->getApplications()
			];
		} catch (\Exception $e) {
			echo '<h2>' . $this->_('Access denied') . '</h2>';
			echo "<p>{$e->getMessage()}</p>";
		}
		return [
			'applications' => new WireArray()
		];
	}

	public function ___executeApplication() {
		$this->headline($this->_('AppApi') . ' ' . $this->_('Application'));
		$this->breadcrumb($this->wire('page')->url, $this->_('AppApi'));
		$this->breadcrumb($this->wire('page')->url . 'applications/', $this->_('Applications'));

		$this->config->scripts->add(
			$this->config->urls->AppApi . 'assets/AppApi.js'
		);

		$action = $this->sanitizer->text($this->input->urlSegment2);

		$id = $this->sanitizer->int($this->input->urlSegment3);
		if ($action === 'new') {
			return [
				'application' => false,
				'action' => $action,
			];
		}
		if ($this->input->urlSegment3 === '' || empty($id)) {
			return [
				'application' => false,
				'action' => $action,
				'locked' => true,
				'message' => 'Missing ID'
			];
		}

		try {
			$application = $this->getApplication($id);
			if ($action === 'edit') {
				return [
					'application' => $application,
					'action' => $action
				];
			} elseif ($action === 'delete') {
				$application->delete();
				$this->notices->add(new NoticeMessage(sprintf($this->_('The application was successfully deleted: %s'), $id)));
				$this->session->redirect($this->wire('page')->url . 'applications/');

				return [
					'application' => false,
					'action' => $action,
					'locked' => true,
					'message' => sprintf($this->_('The application was successfully deleted: %s'), $id)
				];
			}
		} catch (\Exception $e) {
			return [
				'application' => false,
				'action' => $action,
				'locked' => true,
				'message' => $e->getMessage()
			];
		}

		return [
			'application' => false,
			'action' => $action,
			'locked' => true,
		];
	}

	public function ___executeApikey() {
		$this->headline($this->_('AppApi') . ' ' . $this->_('Apikey'));
		$this->breadcrumb($this->wire('page')->url, $this->_('AppApi'));
		$this->breadcrumb($this->wire('page')->url . 'applications/', $this->_('Applications'));

		$this->config->scripts->add(
			$this->config->urls->AppApi . 'assets/AppApi.js'
		);

		$action = $this->sanitizer->text($this->input->urlSegment2);

		$id = $this->sanitizer->int($this->input->urlSegment3);

		if ($this->input->urlSegment3 === '' || empty($id)) {
			return [
				'apikey' => false,
				'application' => false,
				'action' => $action,
				'locked' => true,
				'message' => 'Missing ID'
			];
		}

		if ($action === 'new') {
			try {
				$application = $this->getApplication($id);
				$this->breadcrumb($this->wire('page')->url . 'application/edit/' . $application->getID(), $application->getTitle());
				return [
					'application' => $application,
					'apikey' => false,
					'action' => $action
				];
			} catch (\Exception $e) {
				return [
					'apikey' => false,
					'application' => false,
					'action' => $action,
					'locked' => true,
					'message' => $e->getMessage()
				];
			}
		}

		try {
			$apikey = $this->getApikey($id);
			$application = $this->getApplication($apikey->getApplicationID());
			$this->breadcrumb($this->wire('page')->url . 'application/edit/' . $application->getID(), $application->getTitle());

			if ($action === 'edit') {
				return [
					'apikey' => $apikey,
					'application' => $application,
					'action' => $action
				];
			} elseif ($action === 'delete') {
				$apikey->delete();

				$this->notices->add(new NoticeMessage(sprintf($this->_('The apikey was successfully deleted: %s'), $id)));
				$this->session->redirect($this->wire('page')->url . 'application/edit/' . $application->getID());

				return [
					'apikey' => false,
					'application' => false,
					'action' => $action,
					'locked' => true,
					'message' => sprintf($this->_('The apikey was successfully deleted: %s'), $id)
				];
			}
		} catch (\Exception $e) {
			return [
				'apikey' => false,
				'application' => false,
				'action' => $action,
				'locked' => true,
				'message' => $e->getMessage()
			];
		}

		return [
			'apikey' => false,
			'application' => false,
			'action' => $action,
			'locked' => true,
		];
	}

	public function ___executeApptoken() {
		$this->headline($this->_('AppApi') . ' ' . $this->_('Apptoken'));
		$this->breadcrumb($this->wire('page')->url, $this->_('AppApi'));
		$this->breadcrumb($this->wire('page')->url . 'applications/', $this->_('Applications'));

		$this->config->scripts->add(
			$this->config->urls->AppApi . 'assets/AppApi.js'
		);

		$action = $this->sanitizer->text($this->input->urlSegment2);

		$id = $this->sanitizer->int($this->input->urlSegment3);

		if ($this->input->urlSegment3 === '' || empty($id)) {
			return [
				'apptoken' => false,
				'application' => false,
				'action' => $action,
				'locked' => true,
				'message' => 'Missing ID'
			];
		}

		if ($action === 'new') {
			try {
				$application = $this->getApplication($id);
				$this->breadcrumb($this->wire('page')->url . 'application/edit/' . $application->getID(), $application->getTitle());
				return [
					'application' => $application,
					'apptoken' => false,
					'action' => $action
				];
			} catch (\Exception $e) {
				return [
					'apptoken' => false,
					'application' => false,
					'action' => $action,
					'locked' => true,
					'message' => $e->getMessage()
				];
			}
		}

		try {
			$apptoken = $this->getApptoken($id);
			$application = $this->getApplication($apptoken->getApplicationID());
			$this->breadcrumb($this->wire('page')->url . 'application/edit/' . $application->getID(), $application->getTitle());

			if ($action === 'edit') {
				return [
					'apptoken' => $apptoken,
					'application' => $application,
					'action' => $action
				];
			} elseif ($action === 'delete') {
				$apptoken->delete();

				$this->notices->add(new NoticeMessage(sprintf($this->_('The apptoken was successfully deleted: %s'), $id)));
				$this->session->redirect($this->wire('page')->url . 'application/edit/' . $application->getID());

				return [
					'apptoken' => false,
					'application' => false,
					'action' => $action,
					'locked' => true,
					'message' => sprintf($this->_('The apptoken was successfully deleted: %s'), $id)
				];
			}
		} catch (\Exception $e) {
			return [
				'apptoken' => false,
				'application' => false,
				'action' => $action,
				'locked' => true,
				'message' => $e->getMessage()
			];
		}

		return [
			'apptoken' => false,
			'application' => false,
			'action' => $action,
			'locked' => true,
		];
	}

	public function ___executeEndpoints() {
		$this->headline($this->_('AppApi') . ' ' . $this->_('Endpoints'));

		$this->config->styles->add(
			$this->config->urls->AppApi . 'assets/AppApi.css'
		);

		$action = $this->sanitizer->text($this->input->urlSegment2);

		try {
			$router = new Router();
			$endpoints = $router->getRoutesWithoutDuplicates($this->registeredRoutes, true);

			return [
				'host' => $this->wire('config')->urls->httpRoot,
				'basePath' => $this->endpoint,
				'endpointUrl' => $this->wire('config')->urls->httpRoot . $this->endpoint,
				'endpoints' => $endpoints,
				'action' => $action
			];
		} catch (\Exception $e) {
			echo '<h2>' . $this->_('Access denied') . '</h2>';
			echo "<p>{$e->getMessage()}</p>";
		}
		return [
			'host' => $this->wire('config')->urls->httpRoot,
			'basePath' => $this->endpoint,
			'endpointUrl' => $this->wire('config')->urls->httpRoot . $this->endpoint,
			'endpoints' => new WireArray(),
			'action' => $action
		];
	}

	public function getApplication($id) {
		$application = false;
		$applicationID = $this->sanitizer->int($id);
		if (!empty($id)) {
			$db = wire('database');
			$query = $db->prepare('SELECT * FROM ' . AppApi::tableApplications . ' WHERE `id`=:id;');
			$query->closeCursor();

			$query->execute([
				':id' => $applicationID
			]);
			$queueRaw = $query->fetch(\PDO::FETCH_ASSOC);

			if (!$queueRaw) {
				throw new Wire404Exception();
			}
			$application = new Application($queueRaw);
		}

		return $application;
	}

	public function getApplications() {
		$applications = new WireArray();
		try {
			$db = wire('database');
			$query = $db->prepare('SELECT * FROM ' . AppApi::tableApplications . ';');
			$query->closeCursor();
			$query->execute();
			$queueRaw = $query->fetchAll(\PDO::FETCH_ASSOC);

			foreach ($queueRaw as $queueItem) {
				if (!isset($queueItem['id']) || empty($queueItem['id'])) {
					continue;
				}

				try {
					$application = new Application($queueItem);
					if ($application->isValid()) {
						$applications->add($application);
					}
				} catch (\Exception $e) {
				}
			}
		} catch (\Exception $e) {
			// return empty $application-array if error
		}
		return $applications;
	}

	protected function getApikey($id) {
		$apikey = false;
		try {
			$apikeyID = $this->sanitizer->int($id);
			if (!empty($id)) {
				$db = wire('database');
				$query = $db->prepare('SELECT * FROM ' . AppApi::tableApikeys . ' WHERE `id`=:id;');
				$query->closeCursor();

				$query->execute([
					':id' => $apikeyID
				]);
				$queueRaw = $query->fetch(\PDO::FETCH_ASSOC);
				$apikey = new Apikey($queueRaw);
			}
		} catch (\Exception $e) {
			return false;
		}

		return $apikey;
	}

	protected function getApptoken($id) {
		$apptoken = false;
		try {
			$apptokenID = $this->sanitizer->int($id);
			if (!empty($id)) {
				$db = wire('database');
				$query = $db->prepare('SELECT * FROM ' . AppApi::tableApptokens . ' WHERE `id`=:id;');
				$query->closeCursor();

				$query->execute([
					':id' => $apptokenID
				]);
				$queueRaw = $query->fetch(\PDO::FETCH_ASSOC);
				$apptoken = new Apptoken($queueRaw);
			}
		} catch (\Exception $e) {
			return false;
		}

		return $apptoken;
	}

	public function init() {
		// Let endpoint fall back to 'api' if not set:
		if (!$this->endpoint) {
			$this->endpoint = 'api';
		}
		$endpoint = $endpoint = str_replace('/', "\/", $this->endpoint);

		if (!@$this->wire('modules')->getConfig('AppApi', 'deactivate_url_hook')) {
			$this->addHook('/' . $endpoint . '\/?.*', $this, 'handleApiRequest');
		}
		$this->addHookBefore('ProcessPageView::pageNotFound', $this, 'handleApiRequest');
	}

	protected function checkIfApiRequest() {
		$url = $this->sanitizer->url($_SERVER['REQUEST_URI']);

		// support / in endpoint url:
		$endpoint = str_replace('/', "\/", $this->endpoint);

		$regex = '/^\/' . $endpoint . '\/?.*/m';
		preg_match($regex, $url, $matches);

		return !!$matches;
	}

	public function ___handleApiRequest(HookEvent $event) {
		if ($this->checkIfApiRequest()) {
			$this->apiCall = true;
			Auth::getInstance()->initApikey();
			$router = new Router();
			$router->go($this->registeredRoutes);
			$event->replace = true;
		}
	}

	public function ___isApiCall() {
		return !!$this->apiCall;
	}

	public function ___getCurrentApplication() {
		return $this->apiCall ? Auth::getInstance()->getApplication() : false;
	}

	public function getAuth() {
		return Auth::getInstance();
	}

	/**
	 * Helper method to get a string description for an HTTP status code. Is used if server doesn't support http_response_code().
	 * See https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
	 * @param integer 	$status  	http-statuscode
	 * @return string 	standardized message for the requested statuscode
	 */
	private static function getStatusCodeMessage($status) {
		$codes = [
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
			440 => 'Login Timeout',
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
		];

		return (isset($codes[$status])) ? $codes[$status] : '';
	}

	/**
	 * Helper method to send a HTTP response code/message. Transforms a $body-array (or object) to json.
	 * @param  integer $status       	statuscode for the server-response (See https://en.wikipedia.org/wiki/List_of_HTTP_status_codes). (Default: 200)
	 * @param  string  $body         	body for the response. If body is an array or object and $content_type is 'application/json', the body will be json_encoded automatically.
	 * @param  string  $content_type 	The content-type of the response. Default value is 'text/html'. If an array or object is given as $body, 'application/json' will be default.
	 */
	public static function sendResponse(int $status = 200, $body = '', $content_type = false) {
		// Set status header:

		if (function_exists('http_response_code')) {
			http_response_code($status);
		} else {
			// Fallback to custom method if http_response_code is not supported
			$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
			$statusMessage = self::getStatusCodeMessage($status);
			$status_header = $protocol . ' ' . $status . ' ' . $statusMessage;
			header($status_header);
		}

		// Set content-type header, if its not explicitly set:
		if (!$content_type) {
			$content_type = 'text/html';
			if (is_array($body) || is_object($body)) {
				$content_type = 'application/json';
			}
		}
		header('Content-type: ' . $content_type);

		// Encode content to json if array or object and content-type is application/json:
		if ($content_type === 'application/json' && (is_array($body) || is_object($body))) {
			$jsonbody = json_encode($body);
			if ($jsonbody !== false) {
				$body = $jsonbody;
			}
		}

		echo $body;
		exit();
	}

	/**
	 * Helper function, to convert common PHP-Objects to arrays which can be output in ajax.
	 * @param  Object $content
	 * @return array
	 */
	public static function getAjaxOf($content) {
		$output = [];

		if ($content instanceof PageFiles) {
			foreach ($content as $file) {
				$output[] = self::getAjaxOf($file);
			}
		} elseif ($content instanceof PageFile) {
			$output = [
				'basename' => $content->basename,
				'name' => $content->name,
				'description' => $content->description,
				'created' => $content->created,
				'modified' => $content->modified,
				'filesize' => $content->filesize,
				'filesizeStr' => $content->filesizeStr,
				'page_id' => $content->page->id,
				'ext' => $content->ext
			];

			if ($content instanceof PageImage) {
				$output['basename_mini'] = $content->size(600, 0)->basename;
				$output['width'] = $content->width;
				$output['height'] = $content->height;
				$output['dimension_ratio'] = round($content->width / $content->height, 2);

				if ($content->original) {
					$output['original'] = [
						'basename' => $content->original->basename,
						'name' => $content->original->name,
						'filesize' => $content->original->filesize,
						'filesizeStr' => $content->original->filesizeStr,
						'ext' => $content->original->ext,
						'width' => $content->original->width,
						'height' => $content->original->height,
						'dimension_ratio' => round($content->original->width / $content->original->height, 2)
					];
				}
			}

			// Output custom filefield-values (since PW 3.0.142)
			$fieldValues = $content->get('fieldValues');
			if (!empty($fieldValues) && is_array($fieldValues)) {
				foreach ($fieldValues as $key => $value) {
					$output[$key] = $value;
				}
			}
		} elseif ($content instanceof Template && $content->id) {
			$output = [
				'id' => $content->id,
				'name' => $content->name,
				'label' => $content->label
			];
		} elseif ($content instanceof PageArray) {
			foreach ($content as $page) {
				$output[] = self::getAjaxOf($page);
			}
		} elseif ($content instanceof SelectableOptionArray) {
			foreach ($content as $item) {
				$output[] = self::getAjaxOf($item);
			}
		} elseif ($content instanceof Page && $content->id) {
			$output = [
				'id' => $content->id,
				'name' => $content->name,
				'title' => $content->title,
				'created' => $content->created,
				'modified' => $content->modified,
				'url' => $content->url,
				'httpUrl' => $content->httpUrl,
				'template' => self::getAjaxOf($content->template)
			];
		} elseif ($content instanceof SelectableOption) {
			$output = [
				'id' => $content->id,
				'title' => $content->title,
				'value' => $content->value
			];
		}

		return $output;
	}

	/**
	 * Allows an external module to register custom routes
	 *
	 * @param string $endpoint
	 * @param array $routeDefinition
	 *
	 * @return boolean
	 */
	public function registerRoute($endpoint, $routeDefinition) {
		if (!is_string($endpoint) || empty($endpoint) || !is_array($routeDefinition)) {
			return false;
		}

		$item = [
			'routeDefinition' => $routeDefinition
		];

		try {
			$trace = debug_backtrace();

			if (isset($trace[0]['file'])) {
				$item['trace'] = [
					'file' => $trace[0]['file'] ?? '',
					'line' => $trace[0]['line'] ?? '',
					'class' => $trace[0]['class'] ?? '',
					'function' => $trace[0]['function'] ?? ''
				];
			}
		} catch (\Exception $e) {
		}

		$this->registeredRoutes[$endpoint] = $item;

		return true;
	}

	/**
	 * Replaces placeholders in a text.
	 *
	 * @param string $text
	 * @param array $replacements key-value store of replacements (key is replacement-name)
	 *
	 * return string
	 */
	public static function replacePlaceholders($text, $replacements) {
		if (!is_array($replacements) || !is_string($text)) {
			return $text;
		}

		$output = '' . $text;
		foreach ($replacements as $key => $value) {
			$output = str_replace('{{' . $key . '}}', $value, $output);
		}

		return $output;
	}
}
