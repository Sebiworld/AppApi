<?php namespace ProcessWire;

require_once wire('config')->paths->RestApi . "vendor/autoload.php";
require_once wire('config')->paths->RestApi . "RestApiHelper.php";

require_once __DIR__ . "/Example.php";

$routes = [
  ['OPTIONS', 'test', RestApiHelper::class, 'preflight', ['auth' => false]], // this is needed for CORS Requests
  ['GET', 'test', Example::class, 'test'],
  
  'users' => [
    ['OPTIONS', '', RestApiHelper::class, 'preflight', ['auth' => false]], // this is needed for CORS Requests
    ['GET', '', Example::class, 'getAllUsers', ["auth" => false]],
  ],
];