<?php
namespace ProcessWire;

class DefaultRoutes {
	private static $routes = [
		['*', '', RestApiHelper::class, 'noEndPoint', ['auth' => false]],

		'auth' => [
			['OPTIONS', ''],
			['POST', '', Auth::class, 'login'],
			['DELETE', '', Auth::class, 'logout', ['auth' => false]]
		],
		
		'access' => [
			['OPTIONS', ''],
			['POST', '', Auth::class, 'access'],
		],
	];

	public static function get() {
		return self::$routes;
	}
}