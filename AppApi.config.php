<?php

$config = [
	'endpoint' => [
		'type' => 'text',
		'label' => 'API Endpoint',
		'description' => 'Endpoint under which your API should be available',
		'pattern' => '[a-z0-9-/]+',
		'minlength' => 1,
		'required' => true,
		'value' => 'api',
		'notes' => "('a-z', 0-9, '-' and '/' allowed, Default: 'api')\nFor subdirectories use e.g. subdir/api (no leading slash)"
	],
	'routes_path' => [
		'type' => 'text',
		'label' => 'Path to Routes.php',
		'value' => 'site/api/Routes.php',
		'notes' => 'default: site/api/Routes.php',
		'description' => 'Location of the Routes.php file, where AppApi will find the $routes definition array. Base of path: ProcessWire-Root (Location of index.php)'
	],
	'access_logging' => [
		'type' => 'checkbox',
		'label' => 'Activate Access-Logging',
		'notes' => 'Will write access-data in "appapi-access.txt" log'
	],
	'deactivate_url_hook' => [
		'type' => 'checkbox',
		'label' => 'Deactivate URL Hook',
		'notes' => 'Will deactivate route-handling by ProcessWire URL hook and fallback to ProcessPageView::pageNotFound hook.'
	],
	'urls_relative_to_root' => [
		'type' => 'checkbox',
		'label' => 'Use URLs relative to ProcessWire root',
		'notes' => 'If activated, all URLs returned by the api endpoints will be relative to the ProcessWire root URL. Can be useful if ProcessWire is installed in a sub-directory.'
	],
	'disable_automatic_access_control_headers' => [
		'type' => 'checkbox',
		'label' => 'Disable the automatic setting of Access-Control-Allow-Origin headers',
		'notes' => 'If activated, AppApi will not add any access-control-headers by itself. You need to take care of that on your own to prevent CORS issues.'
	],
];
