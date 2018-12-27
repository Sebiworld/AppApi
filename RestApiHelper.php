<?php namespace ProcessWire;

class RestApiHelper
{
  public static function preflight() {
    return;
  }

  public static function noEndPoint() {
    return 'no endpoint defined';
  }
  
  public static function checkAndSanitizeRequiredParameters($data, $params) {
    foreach ($params as $param) {
      // Split param: Format is name|sanitizer
      $name = explode('|', $param)[0];

      // Check if Param exists
      if (!isset($data->$name)) throw new \Exception("Required parameter: '$param' missing!", 400);

      $sanitizer = explode('|', $param);

      // Sanitize Data
      // If no sanitizer is defined, use the text sanitizer as default
      if (!isset($sanitizer[1])) $sanitizer = 'text';
      else $sanitizer = $sanitizer[1];

      if(!method_exists(wire('sanitizer'), $sanitizer)) throw new \Exception("Sanitizer: '$sanitizer' is no valid sanitizer", 400);
      
      $data->$name = wire('sanitizer')->$sanitizer($data->$name);
    }

    return $data;
  }

  public static function baseUrl() {
    // $site->urls->httpRoot
    return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";
  }
}
