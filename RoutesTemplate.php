<?php namespace ProcessWire;

require_once wire('config')->paths->RestApi . "vendor/autoload.php";
require_once wire('config')->paths->RestApi . "RestApiHelper.php";

$routes = function(\FastRoute\RouteCollector $r) {
	$r->addRoute('GET', '/', RestApiHelper::class . '@noEndpoint');
	$r->addRoute('POST', '/', RestApiHelper::class . '@noEndpoint');
	$r->addRoute('PUT', '/', RestApiHelper::class . '@noEndpoint');
	$r->addRoute('PATCH', '/', RestApiHelper::class . '@noEndpoint');
	$r->addRoute('DELETE', '/', RestApiHelper::class . '@noEndpoint');
};