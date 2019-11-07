<?php
namespace ProcessWire;

class DefaultRoutes {
	private static $routes = [
		['*', '', RestApiHelper::class, 'noEndPoint', ['auth' => false]],

		'auth' => [
			['OPTIONS', '', ['POST', 'DELETE']],
			['POST', '', Auth::class, 'login'],
			['DELETE', '', Auth::class, 'logout', ['auth' => false]]
		],
		
		'access' => [
			['OPTIONS', '', ['POST']],
			// Disable token-checking for the access-endpoint, because it checks for a valid request-token on itself
			['POST', '', Auth::class, 'access', ['handle_authentication' => false]]
		],

		'current_user' => [
			['OPTIONS', '', ['GET']],
			['GET', '', Auth::class, 'currentUser'],
		]
	];

	public static function get() {
		return self::$routes;
	}
}