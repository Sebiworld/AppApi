<?php

$config = array(
	'endpoint' => array(
		'type' => 'text',
		'label' => 'API Endpoint',
		'description' => "Endpoint under which your API should be available",
		'pattern' => '[a-z0-9-/]+',
		'minlength' => 1,
		'required' => true,
		'value' => 'api',
		'notes' => "('a-z', 0-9, '-' and '/' allowed, Default: 'api')\nFor subdirectories use e.g. subdir/api (no leading slash)"
	),
	'routes_path' => array(
		'type'        => 'text',
		'label'       => 'Path to Routes.php',
		'value'       => 'site/api/Routes.php',
		'notes'       => 'default: site/api/Routes.php',
		'description' => 'Location of the Routes.php file, where AppApi will find the $routes definition array. Base of path: ProcessWire-Root (Location of index.php)'
	),
	'access_logging' => array(
		'type'        => 'checkbox',
		'label'       => 'Activate Access-Logging',
		'notes' => 'Will write access-data in "appapi-access.txt" log'
	)
);