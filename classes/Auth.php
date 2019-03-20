<?php
namespace ProcessWire;

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/RestApiHelper.php";

use \Firebase\JWT\JWT;

class Auth {
	protected static function createAccessToken($args = array()) {
		if(wire('user')->isGuest()) {
			throw new \Exception('user is not logged in', 401);
		}

		if(!isset(wire('modules')->RestApi->jwtSecret)) {
			throw new \Exception('No JWT secret defined. Please adjust settings in Module RestApi', 500);
		}

		$issuedAt = time();
		$notBefore = $issuedAt;
		$expire = $notBefore + wire('config')->sessionExpireSeconds;
		$serverName = wire('config')->httpHost;

		$token = array(
      		"iss" => $serverName, // issuer
      		"aud" => $serverName, // audience
      		"iat" => $issuedAt, // issued at
      		"nbf" => $notBefore, // valid not before
      		"exp" => $expire, // token expire time
      		"userId" => wire('user')->id
      	);

      	$token = array_merge($token, $args);

		$jwt = JWT::encode($token, wire('modules')->RestApi->jwtSecret, 'HS256');

		$response = new \StdClass();
		$response->jwt = $jwt;

		return $response;
	}

	public static function login($data) {
		RestApiHelper::checkAndSanitizeRequiredParameters($data, ['username|selectorValue', 'password|string']);

		$user = wire('users')->get($data->username);

    	// if(!$user->id) throw new \Exception("User with username: $data->username not found", 404);
    	// prevent username sniffing by just throwing a general exception:
		if(!$user->id) throw new \Exception("Login not successful", 401);

		$loggedIn = wire('session')->login($data->username, $data->password);

		if($loggedIn) {
			if(wire('modules')->RestApi->authMethod === 'session') return 'logged in: ' . wire('user')->name;
			if(wire('modules')->RestApi->authMethod === 'jwt') return self::createJWT();
		}
		else throw new \Exception("Login not successful", 401);
	}

	public static function logout() {
		$username = wire('user')->name;
		wire('session')->logout(wire('user'));
		return "logged out: $username";
	}

	/**
	 * Checks, if an access-token is present and restores the browser-session for it.
	 * @return boolean
	 */
	public static function isValidTokenPresent(){
		$token = self::getBearerToken();
		if($token === null || !is_string($token) || empty($token)) return false;



		return true;
	}

	protected static function getBearerToken(){
		$authorizationHeader = self::getAuthorizationHeader();
		if($authorizationHeader === null || !is_string($authorizationHeader) || strlen($authorizationHeader) < 7) return null;
		if(substr($authorizationHeader, 0, 7) !== "Bearer ") return null;
		return substr($authorizationHeader, 0, 7);
	}

	protected static function getAuthorizationHeader() {
		if(function_exists("apache_request_headers")) {
			foreach (apache_request_headers() as $key => $value) {
				if(strtolower($key) === 'authorization') return $value;
				else if(strtolower($key) === 'http_authorization') return $value;
			}
		}

		foreach ($_SERVER as $key => $value) {
			if(strtolower($key) === 'authorization') return $value;
			else if(strtolower($key) === 'http_authorization') return $value;
		}

		return null;
	}
}