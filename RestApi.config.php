<?php

$config = array(
  'useJwtAuth' => array(
    'type' => 'checkbox',
    'label' => 'Authentication',
    'description' => 'If you check this, users have to authenticate to be able to use the API. Learn more: [insert url]', 
    'required' => false, 
    'value' => true // default
  ),

  'jwtSecret' => array(
    'type' => 'textarea',
    'label' => 'JWT secret',
    'description' => "JWT Secret (don't share!) to use for JWT Auth. If you change this, every client has to obtain a new JWT token in order to make API calls.\nIMPORTANT: You need to save this page at least once to make the secret permanent! (I found out the hard way…)", 
    'required' => true, 
    'value' => base64_encode(openssl_random_pseudo_bytes(128)),
    'minlength' => 128,
    'showCount' => true
  )
);