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
);