<?php namespace ProcessWire;

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/RestApiHelper.php";

use \Firebase\JWT\JWT;

class Auth {
	public static function preflight() {
		return;
	}

	public static function createJwt() {
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

		$jwt = JWT::encode($token, wire('modules')->RestApi->jwtSecret);

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
}