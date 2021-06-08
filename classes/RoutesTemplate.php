<?php

namespace ProcessWire;

require_once wire('config')->paths->AppApi . 'vendor/autoload.php';
require_once wire('config')->paths->AppApi . 'classes/AppApiHelper.php';

$routes = function (\FastRoute\RouteCollector $r) {
	$r->addRoute('GET', '/', AppApiHelper::class . '@noEndpoint');
	$r->addRoute('POST', '/', AppApiHelper::class . '@noEndpoint');
	$r->addRoute('PUT', '/', AppApiHelper::class . '@noEndpoint');
	$r->addRoute('PATCH', '/', AppApiHelper::class . '@noEndpoint');
	$r->addRoute('DELETE', '/', AppApiHelper::class . '@noEndpoint');
};
