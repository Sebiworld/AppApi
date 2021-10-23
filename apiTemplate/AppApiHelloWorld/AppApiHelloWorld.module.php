<?php

namespace ProcessWire;

/**
 * AppApiHelloWorld adds the /hello-world endpoint to the AppApi routes definition.
 */
class AppApiHelloWorld extends WireData implements Module {
	public static function getModuleInfo() {
		return [
			// Change the following infos to your own texts:
			'title' => 'AppApi - Hello World',
			'summary' => 'AppApi-Module that demonstrates how to add a simple module-endpoint.',
			'version' => '1.0.0',
			'author' => 'Sebastian Schendel',
			'icon' => 'terminal',
			'href' => 'https://modules.processwire.com/modules/app-api/',

			'requires' => [
				'PHP>=7.2.0',
				'ProcessWire>=3.0.98',
				'AppApi>=1.2.0'		// You need AppApi in v1.2.0 or higher!
			],

			'autoload' => true,
			'singular' => true
		];
	}

	/**
	 * The init() function will get called automatically by ProcessWire
	 */
	public function init() {
		$module = $this->wire('modules')->get('AppAPI');

		// Register your custom route-definition:
		$module->registerRoute(
			'hello-world', // endpoint-name
			[
				['OPTIONS', '', ['GET']],
				['GET', '', AppApiHelloWorld::class, 'sayHello'] // Calls the static function sayHello in the module class

			]
		);
	}

	public static function sayHello($data) {
		// This will be returned as JSON if the endpoint is called
		return [
			'success' => true,
			'message' => 'Hello World!'
		];
	}
}
