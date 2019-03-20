<?php
namespace ProcessWire;

class DefaultRoutes {
	private static $routes = [
		['*', '', RestApiHelper::class, 'noEndPoint', ['auth' => false]],

		'auth' => [
			['OPTIONS', '', RestApiHelper::class, 'preflight', ['auth' => false]],
			['POST', '', Auth::class, 'login', ['auth' => false]],
			['DELETE', '', Auth::class, 'logout', ['auth' => false]]
		]
	];

	public static function get() {
		return self::$routes;
	}
}