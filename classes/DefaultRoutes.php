<?php
namespace ProcessWire;

class DefaultRoutes {
	private static $routes = [
		['*', '', AppApiHelper::class, 'noEndPoint', ['auth' => false]],

		'auth' => [
			['OPTIONS', '', ['GET', 'POST', 'DELETE']],
			['GET', '', Auth::class, 'currentUser', [], [
				// documentation
				'summary' => 'Get the current user'
			]],
			['POST', '', Auth::class, 'login', [], [
				// documentation
				'summary' => 'Login User'
			]],
			['DELETE', '', Auth::class, 'logout', ['auth' => false], [
				// documentation
				'summary' => 'Logout User'
			]]
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
