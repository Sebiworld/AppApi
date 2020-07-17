<?php
namespace ProcessWire;

class DefaultRoutes {
	private static $routes = [
		['*', '', AppApiHelper::class, 'noEndPoint', ['auth' => false]],

		'auth' => [
			['OPTIONS', '', ['GET', 'POST', 'DELETE']],
			['GET', '', Auth::class, 'currentUser'],
			['POST', '', Auth::class, 'login'],
			['DELETE', '', Auth::class, 'logout', ['auth' => false]]
		],

		'auth/access' => [
			['OPTIONS', '', ['POST']],
			// Disable token-checking for the access-endpoint, because it checks for a valid request-token on itself
			['POST', '', Auth::class, 'access', ['handle_authentication' => false]]
		]
	];

	public static function get() {
		return self::$routes;
	}
}