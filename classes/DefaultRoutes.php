<?php
namespace ProcessWire;

class DefaultRoutes {
	private static $routes = [
		['*', '', AppApiHelper::class, 'noEndPoint', ['auth' => false]],

		'auth' => [
			['OPTIONS', '', ['GET', 'POST', 'DELETE'], [], [], [
				'summary' => 'Preflight options',
				'tags' => ['Authentication'],
				'security' => [
					['apiKey' => []],
					['bearerAuth' => []]
				],
			]],
			['GET', '', Auth::class, 'currentUser', [], [
				// documentation
				'summary' => 'Get the current user',
				'description' => 'Get the user from the current session.',
				'operationId' => 'getCurrentUser',
				'tags' => ['Authentication'],
				'security' => [
					['apiKey' => []],
					['bearerAuth' => []]
				],
				'parameters' => [],
				'responses' => [
					'200' => [
						'description' => 'Successful operation',
						'content' => [
							'application/json' => [
								'schema' => [
									'required' => ['id', 'name', 'loggedIn'],
									'type' => 'object',
									'properties' => [
										'id' => [
											'type' => 'integer',
											'format' => 'int64',
											'example' => 42
										],
										'name' => [
											'type' => 'string',
											'example' => 'sebi'
										],
										'loggedIn' => [
											'type' => 'boolean'
										]
									]
								]
							]
						]
					],
					'default' => [
						'description' => 'Unexpected error',
						'content' => [
							'application/json' => [
								'schema' => [
									'$ref' => '#/components/schemas/AppApiException'
								]
							]
						]
					]
				]
			]],
			['POST', '', Auth::class, 'login', [], [
				// documentation
				'summary' => 'Login User',
				'description' => 'Authenticates the user.',
				'operationId' => 'loginUser',
				'tags' => ['Authentication'],
				'security' => [
					['apiKey' => []],
					['basicAuth' => []]
				],
				'requestBody' => [
					'content' => [
						'multipart/form-data' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'username' => [
										'type' => 'string'
									],
									'email' => [
										'type' => 'string'
									],
									'password' => [
										'type' => 'string',
										'format' => 'password'
									]
								]
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'description' => 'Login was successful. Depending on the auth-type that is defined in the application, the response will be only the username (PHP session auth), username and jwt (Single JWT) or username and refresh_token (Double JWT)',
						'content' => [
							'application/json' => [
								'schema' => [
									'oneOf' => [
										[
											'required' => ['username'],
											'type' => 'object',
											'properties' => [
												'username' => [
													'type' => 'string',
													'example' => 'sebi'
												]
											]
										],
										[
											'required' => ['username', 'jwt'],
											'type' => 'object',
											'properties' => [
												'username' => [
													'type' => 'string',
													'example' => 'sebi'
												],
												'jwt' => [
													'type' => 'string'
												]
											]
										],
										[
											'required' => ['username', 'refresh_token'],
											'type' => 'object',
											'properties' => [
												'username' => [
													'type' => 'string',
													'example' => 'sebi'
												],
												'refresh_token' => [
													'type' => 'string'
												]
											]
										]
									]
								]
							]
						]
					],
					'default' => [
						'description' => 'Unexpected error',
						'content' => [
							'application/json' => [
								'schema' => [
									'$ref' => '#/components/schemas/AppApiException'
								]
							]
						]
					]
				]
			]],
			['DELETE', '', Auth::class, 'logout', ['auth' => false], [
				// documentation
				'summary' => 'Logout User',
				'description' => 'Logs the user out and terminates their current session.',
				'operationId' => 'logoutUser',
				'tags' => ['Authentication'],
				'security' => [
					['apiKey' => []],
					['bearerAuth' => []]
				],
				'parameters' => [],
				'responses' => [
					'200' => [
						'description' => 'Logout was successful.',
						'content' => [
							'application/json' => [
								'schema' => [
									'required' => ['success'],
									'type' => 'object',
									'properties' => [
										'success' => [
											'type' => 'boolean'
										]
									]
								]
							]
						]
					],
					'default' => [
						'description' => 'Unexpected error',
						'content' => [
							'application/json' => [
								'schema' => [
									'$ref' => '#/components/schemas/AppApiException'
								]
							]
						]
					]
				]
			]]
		],

		'auth/access' => [
			['OPTIONS', '', ['POST'], [], [], [
				'summary' => 'Preflight options',
				'tags' => ['Authentication'],
				'security' => [
					['apiKey' => []],
					['bearerRefreshAuth' => []]
				],
			]],

			// Disable token-checking for the access-endpoint, because it checks for a valid request-token on itself
			['POST', '', Auth::class, 'access', ['handle_authentication' => false], [
				'summary' => 'Renew access token. Only possible if the auth-type is Double JWT',
				'tags' => ['Authentication'],
				'security' => [
					['apiKey' => []],
					['bearerRefreshAuth' => []]
				],
				'parameters' => [],
				'responses' => [
					'200' => [
						'description' => 'Renewing the access token was successful. This will invalidate the used refresh-token and will return a new refresh-token and a new access-token.',
						'content' => [
							'application/json' => [
								'schema' => [
									'required' => ['refresh_token', 'access_token'],
									'type' => 'object',
									'properties' => [
										'refresh_token' => [
											'type' => 'string'
										],
										'access_token' => [
											'type' => 'string'
										]
									]
								]
							]
						]
					],
					'default' => [
						'description' => 'Unexpected error',
						'content' => [
							'application/json' => [
								'schema' => [
									'$ref' => '#/components/schemas/AppApiException'
								]
							]
						]
					]
				]
			]]
		]
	];

	public static function get() {
		return self::$routes;
	}
}
