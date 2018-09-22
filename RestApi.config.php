<?php

$config = array(
  'useJwtAuth' => array(
    'type' => 'checkbox',
    'label' => 'Authentication',
    'description' => 'If you check this, users have to authenticate to be able to use the API. Learn more: https://github.com/thomasaull/RestApi', 
    'required' => false, 
    'value' => true // default
  ),

  'jwtSecret' => array(
    'type' => 'textarea',
    'label' => 'JWT secret',
    'description' => "JWT Secret (don't share!) to use for JWT Auth. If you change this, every client has to obtain a new JWT token in order to make API calls.",
    'notes' => 'IMPORTANT: You need to save this page at least once to make the secret permanent! (I found out the hard way…)',
    'required' => true, 
    'value' => base64_encode(openssl_random_pseudo_bytes(128)),
    'minlength' => 128,
    'showCount' => true
  ),

  'endpoint' => array(
    'type' => 'text',
    'label' => 'API Endpoint',
    'description' => "Endpoint under which your API should be available", 
    'pattern' => '[a-z-]+',
    'minlength' => 1,
    'required' => true, 
    'value' => 'api',
    'notes' => "('a-z' and '-' allowed, Default: 'api')"
  )
);