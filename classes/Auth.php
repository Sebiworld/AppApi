<?php

namespace ProcessWire;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/AppApiHelper.php';

use \Firebase\JWT\JWT;

class Auth extends WireData {
	protected $apikey = false;
	protected $application = false;

	// Only needed for logging:
	protected $tokenId = false;

	public function ___initApikey() {
		$headers = AppApiHelper::getRequestHeaders();
		$tokenFromGet = $_GET && isset($_GET['api_key']) ? $_GET['api_key'] : '';

		if (!empty($headers['X-API-KEY']) || !empty($tokenFromGet)) {
			$apikey = !empty($headers['X-API-KEY']) ? $headers['X-API-KEY'] : $tokenFromGet;
			try {
				// Get apikey-object:
				$apikeyString = $this->sanitizer->text($apikey);
				$db = $this->wire('database');
				$query = $db->prepare('SELECT * FROM ' . AppApi::tableApikeys . ' WHERE `key`=:key;');
				$query->closeCursor();

				$query->execute([
					':key' => $apikeyString
				]);
				$queueRaw = $query->fetch(\PDO::FETCH_ASSOC);
				$this->apikey = new Apikey($queueRaw);

				// Get application from apikey:
				$query = $db->prepare('SELECT * FROM ' . AppApi::tableApplications . ' WHERE `id`=:id;');
				$query->closeCursor();

				$query->execute([
					':id' => $this->apikey->getApplicationID()
				]);
				$queueRaw = $query->fetch(\PDO::FETCH_ASSOC);
				$this->application = new Application($queueRaw);
			} catch (\Throwable $e) {
			}
		} else {
			try {
				// Get default application that handles requests without an apikey:
				$db = $this->wire('database');
				$query = $db->prepare('SELECT * FROM ' . AppApi::tableApplications . ' WHERE `default_application`=true;');
				$query->closeCursor();
				$query->execute();
				$queueRaw = $query->fetch(\PDO::FETCH_ASSOC);
				if (!$queueRaw || !is_array($queueRaw)) {
					throw new \Exception('No default application set.');
				}
				$this->application = new Application($queueRaw);
			} catch (\Throwable $e) {
			}
		}
	}

	public function getApplication() {
		return $this->application;
	}

	public function isApikeyValid() {
		return ($this->apikey instanceof Apikey && $this->apikey->isAccessable() && $this->application instanceof Application) || ($this->apikey === false && $this->application instanceof Application);
	}

	public function getApikeyLog() {
		if (!$this->isApikeyValid() || !($this->apikey instanceof Apikey)) {
			return 'Apikey-ID: NONE';
		}
		return 'Apikey-ID: ' . $this->apikey->getID();
	}

	public function getApplicationLog() {
		if (!$this->application) {
			return 'Application-ID: NONE';
		}
		return 'Application-ID: ' . $this->application->getID();
	}

	protected function ___createSingleJWTToken($args = []) {
		if ($this->wire('user')->isGuest()) {
			throw new AuthException('user is not logged in', 401);
		}

		$apptoken = new Apptoken($this->application->getID());
		$apptoken->setUser($this->wire('user'));
		$apptoken->setExpirationTime(time() + $this->application->getExpiresIn());

		$tokenArgs = [];
		session_regenerate_id(true);
		$sessionName = session_name();
		$sid = $this->wire('input')->cookie($sessionName);
		if (is_string($sid) && strlen($sid) > 0) {
			$tokenArgs['sid'] = $sid;
		}

		$wiresChallengeWert = $this->wire('input')->cookie($sessionName . '_challenge');
		if (is_string($wiresChallengeWert) && strlen($wiresChallengeWert) > 0) {
			$tokenArgs['sid_challenge'] = $wiresChallengeWert;
		}

		$tokenArgs = array_merge($tokenArgs, $args);

		$jwt = $apptoken->getJWT($this->application->getTokenSecret(), $tokenArgs);

		$apptoken->save();

		return $jwt;
	}

	public function ___doLogin($data) {

    $access = $this->getLogintype($data);

    $accessMethod = $access['method'];
    $allowedLoginTypes = $this->application->getLogintype();
    $params = (object)$access['params'];
    if (
      $accessMethod == 'username-password' &&
      in_array('logintypeUsernamePassword', $allowedLoginTypes)
    ) {
      // Get login params
      $username = $params->username;
      $password = $params->password;

      $user = $this->wire('users')->get('name=' . $username);
  
      // prevent username sniffing by just throwing a general exception:
      if (!$user->id) {
        throw new AuthException('Login not successful', 401);
      }

      $loggedIn = $this->wire('session')->login($user->name, $password);
    }

    if (
      $accessMethod == 'email-password' &&
      in_array('logintypeEmailPassword', $allowedLoginTypes)
    ) {
      // Get login params
      $email = $params->email;
      $password = $params->password;

      $user = $this->wire('users')->get('email=' . $email);
  
      // prevent username sniffing by just throwing a general exception:
      if (!$user->id) {
        throw new AuthException('Login not successful', 401);
      }

      $loggedIn = $this->wire('session')->login($user->name, $password);
    }

		if ($loggedIn) {
			if ($this->application->getAuthtype() === Application::authtypeSession) {
				return [
					'success' => true,
					'username' => $this->wire('user')->name
				];
			}
			if ($this->application->getAuthtype() === Application::authtypeSingleJWT) {
				return [
					'jwt' => $this->createSingleJWTToken(),
					'username' => $this->wire('user')->name
				];
			}
			if ($this->application->getAuthtype() === Application::authtypeDoubleJWT) {
				return [
					'refresh_token' => $this->createRefreshToken(),
					'username' => $this->wire('user')->name
				];
			}
		}

		throw new AuthException('Login not successfull', 401);
	}

	protected function ___createRefreshToken($args = []) {
		if ($this->wire('user')->isGuest()) {
			throw new AuthException('user is not logged in', 401);
		}

		$apptoken = new Apptoken($this->application->getID());
		$apptoken->setUser($this->wire('user'));
		$apptoken->setExpirationTime(time() + $this->application->getExpiresIn()); // Refreshtoken is valid for 30 days

		$jwt = $apptoken->getJWT($this->application->getTokenSecret(), $args);

		if (!$apptoken->save()) {
			throw new InternalServererrorException('Token could not be saved', 500);
		}

		return $jwt;
	}

	public function ___getAccessToken() {
		if ($this->application->getAuthtype() !== Application::authtypeDoubleJWT) {
			throw new AuthException('Your api-key does not support double-jwt authentication.', 400);
		}

		$tokenString = $this->getBearerToken();
		if ($tokenString === null || !is_string($tokenString) || empty($tokenString)) {
			throw new AuthException('No valid refreshtoken found.', 400);
		}

		// throws exception if token is invalid:
		$token = JWT::decode($tokenString, $this->application->getTokenSecret(), ['HS256']);
		if (!is_object($token)) {
			throw new AuthException('Invalid Token', 400);
		}

		$user = $this->wire('users')->get('id=' . $this->wire('sanitizer')->int($token->sub));
		if (!($user instanceof User) || !$user->id) {
			throw new AuthException('Invalid User', 400);
		}

		$refreshtokenFromDB = $this->application->getApptoken($token->jti);
		if (!$refreshtokenFromDB instanceof Apptoken || !$refreshtokenFromDB->isValid()) {
			throw new AuthException('Invalid Token', 400);
		}

		if ($user->isGuest() || $refreshtokenFromDB->getUser()->id !== $user->id) {
			throw new AuthException('Invalid User', 400);
		}

		if (!$refreshtokenFromDB->isAccessable()) {
			throw new RefreshtokenExpiredException();
		}

		if (!$refreshtokenFromDB->matchesWithJWT($token)) {
			throw new RefreshtokenExpiredException();
		}

		$accesstoken = $this->createAccessTokenJWT($user, [
			'rtkn' => $refreshtokenFromDB->getTokenID()
		]);

		$refreshtokenFromDB->setExpirationTime(time() + $this->application->getExpiresIn()); // Refreshtoken is valid for 30 days
		$refreshtokenFromDB->setLastUsed(time());
		if (!$refreshtokenFromDB->save()) {
			throw new InternalServererrorException('Token could not be saved', 500);
		}

		return [
			'access_token' => $accesstoken,
			'refresh_token' => $refreshtokenFromDB->getJWT($this->application->getTokenSecret())
		];
	}

	protected function ___createAccessTokenJWT(User $user, $args = []) {
		if ($user->isGuest()) {
			throw new AuthException('user is not logged in', 401);
		}

		$apptoken = new Apptoken($this->application->getID());
		$apptoken->setUser($user);
		$apptoken->setExpirationTime(time() + $this->wire('config')->sessionExpireSeconds);

		$tokenArgs = [];
		session_regenerate_id(true);
		$sessionName = session_name();
		$wiresWert = $this->wire('input')->cookie($sessionName);
		if (is_string($wiresWert) && strlen($wiresWert) > 0) {
			$tokenArgs['sid'] = $wiresWert;
		}

		$wiresChallengeWert = $this->wire('input')->cookie($sessionName . '_challenge');
		if (is_string($wiresChallengeWert) && strlen($wiresChallengeWert) > 0) {
			$tokenArgs['sid_challenge'] = $wiresChallengeWert;
		}

		$tokenArgs = array_merge($tokenArgs, $args);

		$jwt = $apptoken->getJWT($this->application->getAccesstokenSecret(), $tokenArgs);

		return $jwt;
	}

	public function ___doLogout() {
		// Remove Refresh-Token if Double-JWT:
		try {
			if ($this->application->getAuthtype() === Application::authtypeDoubleJWT) {
				$tokenString = $this->getBearerToken();
				if ($tokenString === null || !is_string($tokenString) || empty($tokenString)) {
					return false;
				}

				// throws exception if token is invalid:
				try {
					$secret = $this->application->getAccesstokenSecret();

					$token = JWT::decode($tokenString, $secret, ['HS256']);
				} catch (\Firebase\JWT\ExpiredException $e) {
					throw new AccesstokenExpiredException();
				} catch (\Firebase\JWT\BeforeValidException $e) {
					throw new AccesstokenNotBeforeException();
				} catch (\Throwable $e) {
					throw new AccesstokenInvalidException();
				}

				if (!is_object($token)) {
					throw new AccesstokenInvalidException();
				}

				$userid = $this->wire('sanitizer')->int($token->sub);
				if (empty($userid) || $userid < 1) {
					throw new AccesstokenInvalidException();
				}

				$user = $this->wire('users')->get('id=' . $userid);
				if (!($user instanceof User) || !$user->id) {
					throw new AccesstokenInvalidException();
				}

				// Get Refreshtoken that was used to generate this accesstoken:
				$refreshtokenFromDB = $this->application->getApptoken($token->rtkn);
				if (!$refreshtokenFromDB instanceof Apptoken || !$refreshtokenFromDB->isValid()) {
					throw new AccesstokenInvalidException();
				}

				if ($user->isGuest() || $refreshtokenFromDB->getUser()->id !== $user->id) {
					throw new AccesstokenInvalidException();
				}

				if (!$refreshtokenFromDB->matchesWithJWT($token)) {
					throw new AccesstokenInvalidException();
				}

				if (!$refreshtokenFromDB->delete()) {
					throw new InternalServererrorException('Token could not be removed', 500);
				}
			}
		} catch (\Throwable $e) {
			// todo log
		}

		$this->wire('session')->logout($this->wire('user'));
		return [
			'success' => true
		];
	}

  /*------------------------------------*\
    Login types
  \*------------------------------------*/
  public function getLogintype($data) {
    $headers = AppApiHelper::getRequestHeaders();

    if (
      isset($data->username) && 
      !empty($this->wire('sanitizer')->pageName($data->username)) && 
      isset($data->password) && 
      !empty('' . $data->password)
    ) {
			// Authentication via POST-Param:
      return [
        'method' => 'username-password',
        'params' => [
          'username' => $data->username,
          'password' => $data->password
        ],
      ];
		}
    
    if (
      isset($data->email) && 
      !empty($this->wire('sanitizer')->email($data->email)) && 
      isset($data->password) && 
      !empty('' . $data->password)) {
			// Authentication via POST-Param with email:
      return [
        'method' => 'email-password',
        'params' => [
          'email' => $data->email,
          'password' => $data->password
        ],
      ];
    }

    // Extract params from headers
    $headersParams = NULL;
    if (
      !empty($headers['PHP_AUTH_USER']) && 
      !empty($headers['PHP_AUTH_PW'])
    ) {
			// Authentication via Authentication-Header:
      $headersParams = (object)[
        'username' => $headers['PHP_AUTH_USER'],
        'password' => $headers['PHP_AUTH_PW']
      ];

		}
    
    if (
      !empty($headers['AUTHORIZATION']) && 
      substr($headers['AUTHORIZATION'], 0, 6) === 'Basic '
    ) {
      // Try manually extracting basic auth:
      $authString = base64_decode(substr($headers['AUTHORIZATION'], 6)) ;
      if ($authString) {
        $authParts = explode(':', $authString, 2);
        if (2 === count($authParts)) {
          $headersParams = (object)[
            'username' => $authParts[0],
            'password' => $authParts[1]
          ];
        }
      }
    }

    if (
      isset($headersParams->username) && 
      !empty($this->wire('sanitizer')->pageName($headersParams->username)) && 
      isset($headersParams->password) && 
      !empty('' . $headersParams->password)
    ) {
      return [
        'method' => 'username-password',
        'params' => [
          'username' => $headersParams->username,
          'password' => $headersParams->password
        ],
      ];
    }

    if (
      isset($headersParams->username) && 
      !empty($this->wire('sanitizer')->email($headersParams->username)) && 
      isset($headersParams->password) && 
      !empty('' . $headersParams->password)
    ) {
      return [
        'method' => 'username-password',
        'params' => [
          'username' => $headersParams->username,
          'password' => $headersParams->password
        ],
      ];
    }
    

    header('WWW-Authenticate: Basic realm="Access denied"');
    throw new AuthException('Login not successful wa', 401);

  }

	/**
	 * Checks for Login-Tokens and authenticates the user in ProcessWire
	 */
	public function ___handleAuthentication() {
		if ($this->application->getAuthtype() === Application::authtypeSingleJWT) {
			return $this->handleToken(true);
		} elseif ($this->application->getAuthtype() === Application::authtypeDoubleJWT) {
			return $this->handleToken();
		}
	}

	protected function ___handleToken($singleJwt = false) {
		try {
			$tokenString = $this->getBearerToken();

			if ($tokenString === null || !is_string($tokenString) || empty($tokenString)) {
				$this->clearSession();
				return false;
			}

			// throws exception if token is invalid:
			try {
				$secret = $this->application->getTokenSecret();
				if (!$singleJwt) {
					$secret = $this->application->getAccesstokenSecret();
				}
				$token = JWT::decode($tokenString, $secret, ['HS256']);
			} catch (\Firebase\JWT\ExpiredException $e) {
				throw new AccesstokenExpiredException();
			} catch (\Firebase\JWT\BeforeValidException $e) {
				throw new AccesstokenNotBeforeException();
			} catch (\Throwable $e) {
				throw new AccesstokenInvalidException();
			}

			if (!is_object($token)) {
				throw new AccesstokenInvalidException();
			}

			$userid = $this->wire('sanitizer')->int($token->sub);
			if (empty($userid) || $userid < 1) {
				throw new AccesstokenInvalidException();
			}

			$user = $this->wire('users')->get('id=' . $userid);
			if (!($user instanceof User) || !$user->id) {
				throw new AccesstokenInvalidException();
			}

			if (!$singleJwt) {
				// Get Refreshtoken that was used to generate this accesstoken:
				$refreshtokenFromDB = $this->application->getApptoken($token->rtkn);
				if (!$refreshtokenFromDB instanceof Apptoken || !$refreshtokenFromDB->isValid()) {
					throw new AccesstokenInvalidException();
				}

				if ($user->isGuest() || $refreshtokenFromDB->getUser()->id !== $user->id) {
					throw new AccesstokenInvalidException();
				}

				if (!$refreshtokenFromDB->isAccessable()) {
					throw new RefreshtokenExpiredException();
				}

				if (!$refreshtokenFromDB->matchesWithJWT($token)) {
					throw new AccesstokenInvalidException();
				}

				$refreshtokenFromDB->setLastUsed(time());
				if (!$refreshtokenFromDB->save()) {
					throw new InternalServererrorException('Token could not be saved', 500);
				}

				$this->tokenId = $refreshtokenFromDB->getID();
			}

			$sessionname = session_name();
			if (isset($token->sid)) {
				$sid = $token->sid;
				if (is_string($sid) && strlen($sid) > 0) {
					$_COOKIE[$sessionname] = $sid;
				}
			}

			if (isset($token->sid_challenge)) {
				$sidChallenge = $token->sid_challenge;
				if (is_string($sidChallenge) && strlen($sid) > 0) {
					$_COOKIE[$sessionname . '_challenge'] = $sidChallenge;
				}
			}
			$this->wire('users')->setCurrentUser($user);
		} catch (\Throwable $e) {
			$this->clearSession();
			throw $e;
		}
	}

	public function ___clearSession() {
		$this->wire('users')->setCurrentUser($this->wire('users')->get('guest'));
	}

	/**
	 * Only used for logging the currently used token
	 *
	 * @return void
	 */
	public function getTokenLog() {
		if ($this->tokenId === false) {
			return false;
		}
		return 'Token-ID: ' . $this->tokenId;
	}

	protected function ___getBearerToken() {
		$authorizationHeader = $this->getAuthorizationHeader();

		if ($authorizationHeader === null || !is_string($authorizationHeader) || strlen($authorizationHeader) < 7) {
			if ($_GET && isset($_GET['authorization'])) {
				$authorizationHeader = $_GET['authorization'];
				if ($authorizationHeader === null || !is_string($authorizationHeader) || strlen($authorizationHeader) < 7) {
					return null;
				}
			} else {
				return null;
			}
		}
		if (substr($authorizationHeader, 0, 7) !== 'Bearer ') {
			return null;
		}
		return trim(substr($authorizationHeader, 7));
	}

	protected function ___getAuthorizationHeader() {
		$headers = AppApiHelper::getRequestHeaders();
		if (!empty($headers['AUTHORIZATION'])) {
			return $headers['AUTHORIZATION'];
		} elseif (!empty($headers['HTTP_AUTHORIZATION'])) {
			return $headers['HTTP_AUTHORIZATION'];
		} elseif (!empty($headers['REDIRECT_HTTP_AUTHORIZATION'])) {
			return $headers['REDIRECT_HTTP_AUTHORIZATION'];
		}

		return null;
	}

	// Make Auth Singleton:
	protected static $mainInstance;

	public static function getInstance() {
		if (self::$mainInstance === null) {
			self::$mainInstance = new Auth();
		}
		return self::$mainInstance;
	}

	// Static functions for Use in Routes:
	public static function login($data) {
		return self::getInstance()->doLogin($data);
	}

	public static function logout() {
		return self::getInstance()->doLogout();
	}

	public static function access() {
		return self::getInstance()->getAccessToken();
	}

	public static function currentUser() {
		return [
			'id' => wire('user')->id,
			'name' => wire('user')->name,
			'loggedIn' => wire('user')->isLoggedIn()
		];
	}
}
